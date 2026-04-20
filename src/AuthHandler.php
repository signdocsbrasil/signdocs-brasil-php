<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api;

use Firebase\JWT\JWT;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use SignDocsBrasil\Api\Errors\AuthenticationException;
use SignDocsBrasil\Api\TokenCache\CachedToken;
use SignDocsBrasil\Api\TokenCache\InMemoryTokenCache;
use SignDocsBrasil\Api\TokenCache\TokenCacheInterface;

/**
 * Handles OAuth2 client_credentials token acquisition for both
 * client_secret and private_key_jwt (ES256) authentication modes.
 *
 * Tokens are cached via a pluggable {@see TokenCacheInterface}. The
 * default {@see InMemoryTokenCache} preserves the pre-1.3 behavior of
 * caching in process memory. Stateless hosts (PHP-FPM, serverless)
 * should inject a shared-store cache (WordPress transients, APCu,
 * Redis, etc.) to avoid fetching a fresh token on every request.
 *
 * Non-final since 1.3.0. Subclassing is supported, but prefer
 * injecting a custom cache over subclassing.
 */
class AuthHandler
{
    private readonly string $tokenUrl;
    private readonly ?GuzzleClient $guzzle;
    private readonly TokenCacheInterface $cache;
    private readonly string $cacheKey;

    public function __construct(
        private readonly string $clientId,
        private readonly ?string $clientSecret = null,
        private readonly ?string $privateKey = null,
        private readonly ?string $kid = null,
        string $baseUrl = 'https://api.signdocs.com.br',
        /** @var string[] */
        private readonly array $scopes = [],
        ?GuzzleClient $guzzle = null,
        ?TokenCacheInterface $cache = null,
    ) {
        $this->tokenUrl = rtrim($baseUrl, '/') . '/oauth2/token';
        $this->guzzle = $guzzle;
        $this->cache = $cache ?? new InMemoryTokenCache();
        $this->cacheKey = self::deriveCacheKey($clientId, $baseUrl, $scopes);
    }

    /**
     * Return a valid access token, fetching or refreshing as needed.
     *
     * @throws AuthenticationException
     */
    public function getAccessToken(): string
    {
        $cached = $this->cache->get($this->cacheKey);
        if ($cached !== null && !$cached->isExpired(microtime(true))) {
            return $cached->accessToken;
        }

        return $this->fetchToken();
    }

    /**
     * Invalidate the cached token so that the next call to getAccessToken()
     * will fetch a fresh token from the authorization server.
     */
    public function invalidate(): void
    {
        $this->cache->delete($this->cacheKey);
    }

    /**
     * Cache key derived deterministically from credentials + base URL +
     * scopes. Hashed so that a leaked cache key cannot be reversed to
     * recover the client ID.
     */
    public static function deriveCacheKey(string $clientId, string $baseUrl, array $scopes): string
    {
        $canonicalScopes = $scopes;
        sort($canonicalScopes);
        $material = $clientId . '|' . rtrim($baseUrl, '/') . '|' . implode(' ', $canonicalScopes);
        return 'signdocs.oauth.' . substr(hash('sha256', $material), 0, 32);
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

            $token = new CachedToken(
                accessToken: (string) $data['access_token'],
                expiresAt: microtime(true) + $expiresIn,
            );

            $this->cache->set($this->cacheKey, $token);

            return $token->accessToken;
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
