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
use SignDocsBrasil\Api\Errors\AuthenticationException;
use SignDocsBrasil\Api\TokenCache\CachedToken;
use SignDocsBrasil\Api\TokenCache\InMemoryTokenCache;
use SignDocsBrasil\Api\TokenCache\TokenCacheInterface;

final class AuthHandlerTest extends TestCase
{
    /** @var array<int, array{request: \Psr\Http\Message\RequestInterface}> */
    private array $history = [];

    private function createAuth(
        MockHandler $mock,
        ?string $clientSecret = 'test-secret',
        ?string $privateKey = null,
        ?string $kid = null,
        ?TokenCacheInterface $cache = null,
    ): AuthHandler {
        $this->history = [];
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($this->history));
        $guzzle = new GuzzleClient(['handler' => $handlerStack]);

        return new AuthHandler(
            clientId: 'test-client',
            clientSecret: $clientSecret,
            privateKey: $privateKey,
            kid: $kid,
            baseUrl: 'https://api.signdocs.com.br',
            scopes: ['transactions:read', 'transactions:write'],
            guzzle: $guzzle,
            cache: $cache,
        );
    }

    private function tokenResponse(string $token = 'tok_123', int $expiresIn = 3600): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'access_token' => $token,
            'expires_in' => $expiresIn,
        ]));
    }

    public function testClientSecretFlow(): void
    {
        $mock = new MockHandler([$this->tokenResponse()]);
        $auth = $this->createAuth($mock);

        $token = $auth->getAccessToken();

        $this->assertSame('tok_123', $token);
        $this->assertCount(1, $this->history);

        $request = $this->history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringContainsString('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));

        $body = (string) $request->getBody();
        $this->assertStringContainsString('grant_type=client_credentials', $body);
        $this->assertStringContainsString('client_id=test-client', $body);
        $this->assertStringContainsString('client_secret=test-secret', $body);
    }

    public function testTokenCaching(): void
    {
        $mock = new MockHandler([$this->tokenResponse('tok_cached', 3600)]);
        $auth = $this->createAuth($mock);

        $t1 = $auth->getAccessToken();
        $t2 = $auth->getAccessToken();

        $this->assertSame('tok_cached', $t1);
        $this->assertSame('tok_cached', $t2);
        $this->assertCount(1, $this->history);
    }

    public function testRefreshWithin30sBuffer(): void
    {
        $mock = new MockHandler([
            $this->tokenResponse('tok_1', 20),  // expires in 20s (< 30s buffer)
            $this->tokenResponse('tok_2', 3600),
        ]);
        $auth = $this->createAuth($mock);

        $t1 = $auth->getAccessToken();
        $this->assertSame('tok_1', $t1);

        // 20s < 30s buffer, next call should refresh
        $t2 = $auth->getAccessToken();
        $this->assertSame('tok_2', $t2);
        $this->assertCount(2, $this->history);
    }

    public function testErrorOnFailedRequest(): void
    {
        $mock = new MockHandler([
            new Response(401, [], 'invalid credentials'),
        ]);
        $auth = $this->createAuth($mock);

        $this->expectException(AuthenticationException::class);
        $auth->getAccessToken();
    }

    public function testInvalidate(): void
    {
        $mock = new MockHandler([
            $this->tokenResponse('tok_1', 3600),
            $this->tokenResponse('tok_2', 3600),
        ]);
        $auth = $this->createAuth($mock);

        $auth->getAccessToken();
        $auth->invalidate();
        $t2 = $auth->getAccessToken();

        $this->assertSame('tok_2', $t2);
        $this->assertCount(2, $this->history);
    }

    public function testScopesSent(): void
    {
        $mock = new MockHandler([$this->tokenResponse()]);
        $auth = $this->createAuth($mock);
        $auth->getAccessToken();

        $body = (string) $this->history[0]['request']->getBody();
        $this->assertStringContainsString('scope=', $body);
    }

    public function testTokenCacheHit(): void
    {
        // Pre-populate the shared cache with a valid token. No HTTP call
        // should be made on getAccessToken().
        $cache = new InMemoryTokenCache();
        $key = AuthHandler::deriveCacheKey(
            clientId: 'test-client',
            baseUrl: 'https://api.signdocs.com.br',
            scopes: ['transactions:read', 'transactions:write'],
        );
        $cache->set($key, new CachedToken(
            accessToken: 'tok_prewarmed',
            expiresAt: microtime(true) + 3600,
        ));

        $mock = new MockHandler([]); // Empty: any HTTP call would throw.
        $auth = $this->createAuth($mock, cache: $cache);

        $token = $auth->getAccessToken();

        $this->assertSame('tok_prewarmed', $token);
        $this->assertCount(0, $this->history);
    }

    public function testTokenCachePersistsAcrossInstances(): void
    {
        // Two separate AuthHandler instances sharing the same cache
        // should reuse the token — simulates PHP-FPM / serverless workers
        // sharing a WP transient / APCu / Redis cache.
        $cache = new InMemoryTokenCache();

        $mock1 = new MockHandler([$this->tokenResponse('tok_shared', 3600)]);
        $auth1 = $this->createAuth($mock1, cache: $cache);
        $t1 = $auth1->getAccessToken();

        $mock2 = new MockHandler([]); // Empty: no HTTP allowed.
        $auth2 = $this->createAuth($mock2, cache: $cache);
        $t2 = $auth2->getAccessToken();

        $this->assertSame('tok_shared', $t1);
        $this->assertSame('tok_shared', $t2);
    }

    public function testTokenCacheExpiry(): void
    {
        // Cache holds a token whose expiresAt is already in the past.
        // getAccessToken() must discard it and fetch fresh.
        $cache = new InMemoryTokenCache();
        $key = AuthHandler::deriveCacheKey(
            clientId: 'test-client',
            baseUrl: 'https://api.signdocs.com.br',
            scopes: ['transactions:read', 'transactions:write'],
        );
        $cache->set($key, new CachedToken(
            accessToken: 'tok_expired',
            expiresAt: microtime(true) - 10,
        ));

        $mock = new MockHandler([$this->tokenResponse('tok_refreshed', 3600)]);
        $auth = $this->createAuth($mock, cache: $cache);

        $token = $auth->getAccessToken();

        $this->assertSame('tok_refreshed', $token);
        $this->assertCount(1, $this->history);
    }

    public function testInvalidateRemovesFromCache(): void
    {
        $cache = new InMemoryTokenCache();
        $mock = new MockHandler([
            $this->tokenResponse('tok_1', 3600),
            $this->tokenResponse('tok_2', 3600),
        ]);
        $auth = $this->createAuth($mock, cache: $cache);

        $auth->getAccessToken();
        $auth->invalidate();
        $t2 = $auth->getAccessToken();

        $this->assertSame('tok_2', $t2);
        $this->assertCount(2, $this->history);

        // After invalidate(), the cache slot for this key is empty.
        $key = AuthHandler::deriveCacheKey(
            clientId: 'test-client',
            baseUrl: 'https://api.signdocs.com.br',
            scopes: ['transactions:read', 'transactions:write'],
        );
        // The second fetch populated it again; sanity check presence.
        $this->assertNotNull($cache->get($key));
    }

    public function testDeriveCacheKeyIsDeterministicAndHashed(): void
    {
        $k1 = AuthHandler::deriveCacheKey('client_acme_123', 'https://api.example/', ['s1', 's2']);
        $k2 = AuthHandler::deriveCacheKey('client_acme_123', 'https://api.example', ['s2', 's1']);
        $k3 = AuthHandler::deriveCacheKey('client_other', 'https://api.example', ['s1', 's2']);

        // Trailing slash + scope order must not change the key.
        $this->assertSame($k1, $k2);
        // Different clientId must produce a different key.
        $this->assertNotSame($k1, $k3);
        // Key must not leak the clientId as plaintext.
        $this->assertStringNotContainsString('client_acme_123', $k1);
        // Key has the expected prefix + fixed-length hash shape.
        $this->assertStringStartsWith('signdocs.oauth.', $k1);
        $this->assertSame(15 + 32, strlen($k1));
    }
}
