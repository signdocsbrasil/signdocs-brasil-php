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
use SignDocsBrasil\Api\Errors\NotFoundException;
use SignDocsBrasil\Api\Errors\RateLimitException;
use SignDocsBrasil\Api\HttpClient;

final class HttpClientTest extends TestCase
{
    /** @var array<int, array{request: \Psr\Http\Message\RequestInterface}> */
    private array $history = [];

    private function createClient(MockHandler $apiMock, int $maxRetries = 0): HttpClient
    {
        $this->history = [];
        $handlerStack = HandlerStack::create($apiMock);
        $handlerStack->push(Middleware::history($this->history));
        $guzzle = new GuzzleClient(['handler' => $handlerStack, 'http_errors' => false]);

        // Create auth with a pre-cached token mock
        $authMock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ])),
        ]);
        $authHandler = HandlerStack::create($authMock);
        $authGuzzle = new GuzzleClient(['handler' => $authHandler]);

        $auth = new AuthHandler(
            clientId: 'test-client',
            clientSecret: 'test-secret',
            baseUrl: 'https://api.signdocs.com.br',
            scopes: ['transactions:read'],
            guzzle: $authGuzzle,
        );
        // Pre-warm the token cache
        $auth->getAccessToken();

        return new HttpClient(
            baseUrl: 'https://api.signdocs.com.br',
            timeout: 30,
            auth: $auth,
            maxRetries: $maxRetries,
            guzzle: $guzzle,
        );
    }

    public function testAuthorizationHeader(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
        ]);
        $client = $this->createClient($mock);
        $client->request('GET', '/v1/test');

        $req = $this->history[0]['request'];
        $this->assertStringStartsWith('Bearer ', $req->getHeaderLine('Authorization'));
    }

    public function testUserAgentHeader(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
        ]);
        $client = $this->createClient($mock);
        $client->request('GET', '/v1/test');

        $req = $this->history[0]['request'];
        $this->assertStringContainsString('signdocs-brasil-php/', $req->getHeaderLine('User-Agent'));
    }

    public function testNoAuthSkipsAuthorization(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"status":"healthy"}'),
        ]);

        // Create without pre-warming auth
        $handlerStack = HandlerStack::create($mock);
        $this->history = [];
        $handlerStack->push(Middleware::history($this->history));
        $guzzle = new GuzzleClient(['handler' => $handlerStack]);

        $authMock = new MockHandler([]);
        $authGuzzle = new GuzzleClient(['handler' => HandlerStack::create($authMock)]);

        $auth = new AuthHandler(
            clientId: 'test-client',
            clientSecret: 'test-secret',
            baseUrl: 'https://api.signdocs.com.br',
            scopes: ['transactions:read'],
            guzzle: $authGuzzle,
        );

        $client = new HttpClient(
            baseUrl: 'https://api.signdocs.com.br',
            timeout: 30,
            auth: $auth,
            maxRetries: 0,
            guzzle: $guzzle,
        );

        $client->request('GET', '/health', noAuth: true);

        $req = $this->history[0]['request'];
        $this->assertFalse($req->hasHeader('Authorization'));
    }

    public function testJsonBody(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"tx_1"}'),
        ]);
        $client = $this->createClient($mock);
        $client->request('POST', '/v1/transactions', body: ['name' => 'test']);

        $req = $this->history[0]['request'];
        $this->assertStringContainsString('application/json', $req->getHeaderLine('Content-Type'));
    }

    public function test204ReturnsNull(): void
    {
        $mock = new MockHandler([
            new Response(204),
        ]);
        $client = $this->createClient($mock);
        $result = $client->request('DELETE', '/v1/webhooks/123');

        $this->assertNull($result);
    }

    public function test400ThrowsBadRequest(): void
    {
        $mock = new MockHandler([
            new Response(400, ['Content-Type' => 'application/json'], json_encode([
                'type' => 'about:blank', 'title' => 'Bad Request', 'status' => 400,
            ])),
        ]);
        $client = $this->createClient($mock);

        $this->expectException(BadRequestException::class);
        $client->request('POST', '/v1/test');
    }

    public function test404ThrowsNotFound(): void
    {
        $mock = new MockHandler([
            new Response(404, ['Content-Type' => 'application/json'], json_encode([
                'type' => 'about:blank', 'title' => 'Not Found', 'status' => 404,
            ])),
        ]);
        $client = $this->createClient($mock);

        $this->expectException(NotFoundException::class);
        $client->request('GET', '/v1/transactions/missing');
    }

    public function test429ThrowsRateLimitWithRetryAfter(): void
    {
        $mock = new MockHandler([
            new Response(429, [
                'Content-Type' => 'application/json',
                'Retry-After' => '5',
            ], json_encode([
                'type' => 'about:blank', 'title' => 'Rate Limited', 'status' => 429,
            ])),
        ]);
        $client = $this->createClient($mock);

        try {
            $client->request('GET', '/v1/test');
            $this->fail('Should have thrown');
        } catch (RateLimitException $e) {
            $this->assertSame(429, $e->getStatus());
            $this->assertSame(5, $e->retryAfterSeconds);
        }
    }

    public function testIdempotencyKeyExplicit(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"tx_1"}'),
        ]);
        $client = $this->createClient($mock);
        $client->requestWithIdempotency('POST', '/v1/transactions', body: ['name' => 'test'], idempotencyKey: 'custom-key');

        $req = $this->history[0]['request'];
        $this->assertSame('custom-key', $req->getHeaderLine('X-Idempotency-Key'));
    }

    public function testIdempotencyKeyAutoGenerated(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"tx_1"}'),
        ]);
        $client = $this->createClient($mock);
        $client->requestWithIdempotency('POST', '/v1/transactions', body: ['name' => 'test']);

        $req = $this->history[0]['request'];
        $key = $req->getHeaderLine('X-Idempotency-Key');
        $this->assertNotEmpty($key);
    }
}
