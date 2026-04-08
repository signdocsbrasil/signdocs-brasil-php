<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SignDocsBrasil\Api\AuthHandler;
use SignDocsBrasil\Api\Errors\BadRequestException;
use SignDocsBrasil\Api\Errors\ServiceUnavailableException;
use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Resources\TransactionsResource;

/**
 * Integration tests for the retry logic exercised through the full HTTP stack.
 *
 * Uses Guzzle MockHandler to queue sequences of responses and verifies
 * that retries are attempted (or not) based on status codes and config.
 */
final class RetryIntegrationTest extends TestCase
{
    /** @var array<int, array{request: \Psr\Http\Message\RequestInterface}> */
    private array $apiHistory = [];

    /**
     * Build a full HttpClient with a mocked auth layer and mocked API layer.
     *
     * @param Response[] $apiResponses Queued API responses
     * @param int        $maxRetries   Maximum retry attempts
     */
    private function buildClient(array $apiResponses, int $maxRetries): HttpClient
    {
        // Auth mock: single token response
        $authMock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ])),
        ]);
        $authStack = HandlerStack::create($authMock);
        $authGuzzle = new GuzzleClient(['handler' => $authStack]);

        $auth = new AuthHandler(
            clientId: 'test-client',
            clientSecret: 'test-secret',
            baseUrl: 'https://api.signdocs.com.br',
            scopes: ['transactions:read'],
            guzzle: $authGuzzle,
        );
        // Pre-warm the token cache
        $auth->getAccessToken();

        // API mock
        $this->apiHistory = [];
        $apiMock = new MockHandler($apiResponses);
        $apiStack = HandlerStack::create($apiMock);
        $apiStack->push(Middleware::history($this->apiHistory));
        $apiGuzzle = new GuzzleClient(['handler' => $apiStack, 'http_errors' => false]);

        return new HttpClient(
            baseUrl: 'https://api.signdocs.com.br',
            timeout: 30,
            auth: $auth,
            maxRetries: $maxRetries,
            guzzle: $apiGuzzle,
        );
    }

    private function jsonResponse(int $status, array $body, array $headers = []): Response
    {
        $headers = array_merge(['Content-Type' => 'application/json'], $headers);
        return new Response($status, $headers, json_encode($body));
    }

    private function okTransactionResponse(): Response
    {
        return $this->jsonResponse(200, [
            'tenantId' => 'abc123',
            'transactionId' => 'tx-001',
            'status' => 'CREATED',
            'purpose' => 'DOCUMENT_SIGNATURE',
            'policy' => ['profile' => 'CLICK_ONLY'],
            'signer' => ['name' => 'Test', 'userExternalId' => 'ext_1'],
            'steps' => [],
            'expiresAt' => '2025-12-31T00:00:00Z',
            'createdAt' => '2025-01-01T00:00:00Z',
            'updatedAt' => '2025-01-01T00:00:00Z',
        ]);
    }

    /**
     * 503 followed by 200: the retry succeeds on the second attempt.
     */
    public function testRetries503ThenSucceeds(): void
    {
        $client = $this->buildClient([
            $this->jsonResponse(503, [
                'type' => 'about:blank',
                'title' => 'Service Unavailable',
                'status' => 503,
            ]),
            $this->okTransactionResponse(),
        ], maxRetries: 3);

        $result = $client->request('GET', '/v1/transactions/tx-001');

        $this->assertSame('tx-001', $result['transactionId']);
        $this->assertGreaterThanOrEqual(2, count($this->apiHistory));
    }

    /**
     * 429 with Retry-After=1 header followed by 200: retries and succeeds.
     */
    public function testRetries429WithRetryAfterThenSucceeds(): void
    {
        $client = $this->buildClient([
            $this->jsonResponse(429, [
                'type' => 'about:blank',
                'title' => 'Too Many Requests',
                'status' => 429,
            ], ['Retry-After' => '1']),
            $this->okTransactionResponse(),
        ], maxRetries: 3);

        $result = $client->request('GET', '/v1/transactions/tx-001');

        $this->assertSame('tx-001', $result['transactionId']);
        $this->assertGreaterThanOrEqual(2, count($this->apiHistory));
    }

    /**
     * 503 x 3 then 200 with maxRetries=3: four total requests, success on fourth.
     */
    public function testRetriesThree503sThenSucceeds(): void
    {
        $client = $this->buildClient([
            $this->jsonResponse(503, ['type' => 'about:blank', 'title' => 'Service Unavailable', 'status' => 503]),
            $this->jsonResponse(503, ['type' => 'about:blank', 'title' => 'Service Unavailable', 'status' => 503]),
            $this->jsonResponse(503, ['type' => 'about:blank', 'title' => 'Service Unavailable', 'status' => 503]),
            $this->okTransactionResponse(),
        ], maxRetries: 3);

        $result = $client->request('GET', '/v1/transactions/tx-001');

        $this->assertSame('tx-001', $result['transactionId']);
        $this->assertCount(4, $this->apiHistory);
    }

    /**
     * 503 x 4 with maxRetries=3: all retries exhausted, throws ServiceUnavailableException.
     */
    public function testExhaustsRetriesThrowsServiceUnavailable(): void
    {
        $client = $this->buildClient([
            $this->jsonResponse(503, ['type' => 'about:blank', 'title' => 'Service Unavailable', 'status' => 503]),
            $this->jsonResponse(503, ['type' => 'about:blank', 'title' => 'Service Unavailable', 'status' => 503]),
            $this->jsonResponse(503, ['type' => 'about:blank', 'title' => 'Service Unavailable', 'status' => 503]),
            $this->jsonResponse(503, ['type' => 'about:blank', 'title' => 'Service Unavailable', 'status' => 503]),
        ], maxRetries: 3);

        $this->expectException(ServiceUnavailableException::class);
        $client->request('GET', '/v1/transactions/tx-001');
    }

    /**
     * Non-retryable 400: no retry, immediate BadRequestException.
     */
    public function testNonRetryable400ThrowsImmediately(): void
    {
        $client = $this->buildClient([
            $this->jsonResponse(400, [
                'type' => 'about:blank',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Invalid input',
            ]),
        ], maxRetries: 3);

        try {
            $client->request('POST', '/v1/transactions');
            $this->fail('Expected BadRequestException to be thrown');
        } catch (BadRequestException $e) {
            $this->assertSame(400, $e->getStatus());
            // Only one request was made - no retries
            $this->assertCount(1, $this->apiHistory);
        }
    }
}
