<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException as GuzzleConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use SignDocsBrasil\Api\Errors\ApiException;
use SignDocsBrasil\Api\Errors\ConnectionException;
use SignDocsBrasil\Api\Errors\TimeoutException;

/**
 * Internal Guzzle wrapper that handles authentication, retries,
 * response parsing, and error mapping.
 */
class HttpClient
{
    private const SDK_VERSION = '1.0.0';

    private readonly GuzzleClient $guzzle;
    private readonly RetryHandler $retry;
    private readonly ?LoggerInterface $logger;

    public function __construct(
        private readonly string $baseUrl,
        private readonly int $timeout,
        private readonly AuthHandler $auth,
        int $maxRetries,
        ?GuzzleClient $guzzle = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->guzzle = $guzzle ?? new GuzzleClient([
            'base_uri' => rtrim($baseUrl, '/') . '/',
            'timeout' => $timeout,
            'connect_timeout' => 10,
            'http_errors' => false,
        ]);

        $this->retry = new RetryHandler($maxRetries);
        $this->logger = $logger;
    }

    /**
     * Execute an HTTP request with authentication, retries, and error handling.
     *
     * @param string                                    $method  HTTP method
     * @param string                                    $path    URL path (relative to base URL)
     * @param array<string, mixed>|null                 $body    JSON body to send
     * @param array<string, string|int|null>|null       $query   Query parameters
     * @param array<string, string>                     $headers Additional headers
     * @param bool                                      $noAuth  Skip Bearer token
     * @param int|null                                  $timeout Per-request timeout in seconds (overrides default)
     * @return array<string, mixed>|null                Decoded JSON response, or null for 204
     *
     * @throws ApiException
     * @throws ConnectionException
     * @throws TimeoutException
     */
    public function request(
        string $method,
        string $path,
        ?array $body = null,
        ?array $query = null,
        array $headers = [],
        bool $noAuth = false,
        ?int $timeout = null,
    ): ?array {
        $startTime = microtime(true);

        for ($attempt = 0;; $attempt++) {
            try {
                $response = $this->doRequest($method, $path, $body, $query, $headers, $noAuth, $timeout);
                $statusCode = $response->getStatusCode();
                $durationMs = (int) round((microtime(true) - $startTime) * 1000);

                if (!$this->retry->isRetryable($statusCode)) {
                    if ($statusCode >= 400) {
                        $this->logger?->warning('request failed', [
                            'method' => $method,
                            'path' => $path,
                            'status' => $statusCode,
                            'duration_ms' => $durationMs,
                        ]);
                    } else {
                        $this->logger?->info('request completed', [
                            'method' => $method,
                            'path' => $path,
                            'status' => $statusCode,
                            'duration_ms' => $durationMs,
                        ]);
                    }

                    return $this->parseResponse($response);
                }

                if (!$this->retry->shouldRetry($attempt, $startTime)) {
                    $this->logger?->warning('request failed', [
                        'method' => $method,
                        'path' => $path,
                        'status' => $statusCode,
                        'duration_ms' => $durationMs,
                    ]);

                    return $this->parseResponse($response);
                }

                $retryAfterSec = $this->parseRetryAfter($response);
                $delay = $this->retry->getDelay($attempt, $retryAfterSec);
                usleep($delay);
            } catch (GuzzleConnectException $e) {
                $durationMs = (int) round((microtime(true) - $startTime) * 1000);

                if (str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'timeout')) {
                    $this->logger?->warning('request failed', [
                        'method' => $method,
                        'path' => $path,
                        'status' => 0,
                        'duration_ms' => $durationMs,
                        'error' => 'timeout',
                    ]);

                    $effectiveTimeout = $timeout ?? $this->timeout;
                    throw new TimeoutException(
                        "Request to {$path} timed out after {$effectiveTimeout}s",
                        0,
                        $e,
                    );
                }

                $this->logger?->warning('request failed', [
                    'method' => $method,
                    'path' => $path,
                    'status' => 0,
                    'duration_ms' => $durationMs,
                    'error' => 'connection',
                ]);

                throw new ConnectionException(
                    "Failed to connect to {$this->baseUrl}{$path}: {$e->getMessage()}",
                    0,
                    $e,
                );
            } catch (GuzzleException $e) {
                $durationMs = (int) round((microtime(true) - $startTime) * 1000);

                $this->logger?->warning('request failed', [
                    'method' => $method,
                    'path' => $path,
                    'status' => 0,
                    'duration_ms' => $durationMs,
                    'error' => 'connection',
                ]);

                throw new ConnectionException(
                    "Request to {$path} failed: {$e->getMessage()}",
                    0,
                    $e,
                );
            }
        }
    }

    /**
     * Execute a request with an idempotency key header.
     *
     * @param string                                    $method         HTTP method
     * @param string                                    $path           URL path
     * @param array<string, mixed>|null                 $body           JSON body
     * @param string|null                               $idempotencyKey Optional explicit key
     * @param array<string, string|int|null>|null       $query          Query parameters
     * @param array<string, string>                     $headers        Additional headers
     * @param int|null                                  $timeout        Per-request timeout in seconds
     * @return array<string, mixed>|null
     */
    public function requestWithIdempotency(
        string $method,
        string $path,
        ?array $body = null,
        ?string $idempotencyKey = null,
        ?array $query = null,
        array $headers = [],
        ?int $timeout = null,
    ): ?array {
        $key = $idempotencyKey ?? $this->generateIdempotencyKey();
        $headers['X-Idempotency-Key'] = $key;

        return $this->request($method, $path, $body, $query, $headers, timeout: $timeout);
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, mixed>|null $query
     * @param array<string, string> $headers
     * @throws GuzzleException
     */
    private function doRequest(
        string $method,
        string $path,
        ?array $body,
        ?array $query,
        array $headers,
        bool $noAuth,
        ?int $timeout = null,
    ): ResponseInterface {
        $options = [
            'headers' => array_merge(
                ['User-Agent' => 'signdocs-brasil-php/' . self::SDK_VERSION],
                $headers,
            ),
        ];

        if ($timeout !== null) {
            $options['timeout'] = $timeout;
        }

        if (!$noAuth) {
            $token = $this->auth->getAccessToken();
            $options['headers']['Authorization'] = "Bearer {$token}";
        }

        if ($body !== null) {
            $options['headers']['Content-Type'] = 'application/json';
            $options['json'] = $body;
        }

        if ($query !== null) {
            $filtered = [];
            foreach ($query as $key => $value) {
                if ($value !== null) {
                    $filtered[$key] = (string) $value;
                }
            }
            if ($filtered !== []) {
                $options['query'] = $filtered;
            }
        }

        return $this->guzzle->request($method, ltrim($path, '/'), $options);
    }

    /**
     * Parse an HTTP response into a decoded array, or throw an ApiException.
     *
     * @return array<string, mixed>|null
     * @throws ApiException
     */
    private function parseResponse(ResponseInterface $response): ?array
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode === 204) {
            return null;
        }

        $rawBody = (string) $response->getBody();
        $contentType = $response->getHeaderLine('Content-Type');
        $body = [];

        if (
            $rawBody !== ''
            && (
                str_contains($contentType, 'application/json')
                || str_contains($contentType, 'application/problem+json')
            )
        ) {
            try {
                $body = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $body = ['message' => $rawBody];
            }
        } elseif ($rawBody !== '') {
            $body = ['message' => $rawBody];
        }

        if ($statusCode >= 400) {
            $retryAfterSec = $this->parseRetryAfter($response);
            throw ApiException::fromResponse($statusCode, $body, $retryAfterSec);
        }

        return $body;
    }

    /**
     * Parse the Retry-After header value.
     */
    private function parseRetryAfter(ResponseInterface $response): ?int
    {
        $header = $response->getHeaderLine('Retry-After');
        if ($header === '') {
            return null;
        }

        $value = (int) $header;
        return $value > 0 ? $value : null;
    }

    /**
     * Generate a UUID v4-style idempotency key.
     */
    private function generateIdempotencyKey(): string
    {
        $data = random_bytes(16);

        // Set version to 0100 (UUID v4)
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10 (RFC 4122 variant)
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
