<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api;

use Firebase\JWT\JWT;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use SignDocsBrasil\Api\Errors\AuthenticationException;

/**
 * Handles OAuth2 client_credentials token acquisition for both
 * client_secret and private_key_jwt (ES256) authentication modes.
 *
 * Caches the access token in memory and refreshes it 30 seconds
 * before expiry.
 */
final class AuthHandler
{
    private readonly string $tokenUrl;
    private ?string $cachedAccessToken = null;
    private ?float $cachedExpiresAt = null;
    private readonly ?GuzzleClient $guzzle;

    public function __construct(
        private readonly string $clientId,
        private readonly ?string $clientSecret = null,
        private readonly ?string $privateKey = null,
        private readonly ?string $kid = null,
        string $baseUrl = 'https://api.signdocs.com.br',
        /** @var string[] */
        private readonly array $scopes = [],
        ?GuzzleClient $guzzle = null,
    ) {
        $this->tokenUrl = rtrim($baseUrl, '/') . '/oauth2/token';
        $this->guzzle = $guzzle;
    }

    /**
     * Return a valid access token, fetching or refreshing as needed.
     *
     * @throws AuthenticationException
     */
    public function getAccessToken(): string
    {
        if (
            $this->cachedAccessToken !== null
            && $this->cachedExpiresAt !== null
            && microtime(true) < ($this->cachedExpiresAt - 30)
        ) {
            return $this->cachedAccessToken;
        }

        return $this->fetchToken();
    }

    /**
     * Invalidate the cached token so that the next call to getAccessToken()
     * will fetch a fresh token from the authorization server.
     */
    public function invalidate(): void
    {
        $this->cachedAccessToken = null;
        $this->cachedExpiresAt = null;
    }

    /**
     * @throws AuthenticationException
     */
    private function fetchToken(): string
    {
        $params = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'scope' => implode(' ', $this->scopes),
        ];

        if ($this->clientSecret !== null) {
            $params['client_secret'] = $this->clientSecret;
        } elseif ($this->privateKey !== null && $this->kid !== null) {
            $params['client_assertion_type'] = 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer';
            $params['client_assertion'] = $this->buildJwtAssertion();
        }

        try {
            $guzzle = $this->guzzle ?? new GuzzleClient([
                'timeout' => 15,
                'connect_timeout' => 10,
            ]);

            $response = $guzzle->post($this->tokenUrl, [
                'form_params' => $params,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ],
            ]);

            $body = (string) $response->getBody();
            $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($data['access_token'])) {
                throw new AuthenticationException(
                    'Token response missing access_token field'
                );
            }

            $expiresIn = (int) ($data['expires_in'] ?? 3600);

            $this->cachedAccessToken = (string) $data['access_token'];
            $this->cachedExpiresAt = microtime(true) + $expiresIn;

            return $this->cachedAccessToken;
        } catch (GuzzleException $e) {
            throw new AuthenticationException(
                "Token request failed: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        } catch (\JsonException $e) {
            throw new AuthenticationException(
                "Failed to parse token response: {$e->getMessage()}",
                0,
                $e,
            );
        }
    }

    /**
     * Build a signed JWT assertion (ES256) for private_key_jwt authentication.
     */
    private function buildJwtAssertion(): string
    {
        $now = time();

        $payload = [
            'iss' => $this->clientId,
            'sub' => $this->clientId,
            'aud' => $this->tokenUrl,
            'exp' => $now + 300,
            'iat' => $now,
            'jti' => $this->generateJti(),
        ];

        return JWT::encode($payload, $this->privateKey, 'ES256', $this->kid);
    }

    /**
     * Generate a unique JWT ID (jti) claim value.
     */
    private function generateJti(): string
    {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes(16));
        }

        return uniqid('jti_', true);
    }
}
