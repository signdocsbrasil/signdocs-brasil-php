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
use SignDocsBrasil\Api\HttpClient;

final class PerRequestTimeoutTest extends TestCase
{
    /** @var array<int, array{request: \Psr\Http\Message\RequestInterface, options: array<string, mixed>}> */
    private array $history = [];

    private function createAuthHandler(): AuthHandler
    {
        $authMock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'access_token' => 'test-token',
                'expires_in' => 3600,
            ])),
        ]);
        $authGuzzle = new GuzzleClient(['handler' => HandlerStack::create($authMock)]);

        $auth = new AuthHandler(
            clientId: 'test-client',
            clientSecret: 'test-secret',
            baseUrl: 'https://api.signdocs.com.br',
            scopes: ['transactions:read'],
            guzzle: $authGuzzle,
        );
        $auth->getAccessToken();

        return $auth;
    }

    private function createClient(MockHandler $mock, int $defaultTimeout = 30): HttpClient
    {
        $this->history = [];
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($this->history));
        $guzzle = new GuzzleClient(['handler' => $handlerStack, 'http_errors' => false]);

        return new HttpClient(
            baseUrl: 'https://api.signdocs.com.br',
            timeout: $defaultTimeout,
            auth: $this->createAuthHandler(),
            maxRetries: 0,
            guzzle: $guzzle,
        );
    }

    public function testPerRequestTimeoutOverridesDefault(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
        ]);
        $client = $this->createClient($mock, defaultTimeout: 30);

        $client->request('GET', '/v1/test', noAuth: true, timeout: 5);

        $this->assertCount(1, $this->history);
        $options = $this->history[0]['options'];
        $this->assertSame(5, $options['timeout']);
    }

    public function testNullTimeoutDoesNotSetRequestOption(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
        ]);
        $client = $this->createClient($mock, defaultTimeout: 30);

        $client->request('GET', '/v1/test', noAuth: true, timeout: null);

        $this->assertCount(1, $this->history);
        $options = $this->history[0]['options'];
        // When no per-request timeout is set, the option should not be present
        // (the default from client construction applies at the Guzzle level)
        $this->assertArrayNotHasKey('timeout', $options);
    }

    public function testPerRequestTimeoutOnPostRequest(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"tx_1"}'),
        ]);
        $client = $this->createClient($mock);

        $client->request('POST', '/v1/transactions', body: ['name' => 'test'], timeout: 60);

        $this->assertCount(1, $this->history);
        $options = $this->history[0]['options'];
        $this->assertSame(60, $options['timeout']);
    }

    public function testPerRequestTimeoutOnIdempotentRequest(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"tx_1"}'),
        ]);
        $client = $this->createClient($mock);

        $client->requestWithIdempotency(
            'POST',
            '/v1/transactions',
            body: ['name' => 'test'],
            timeout: 10,
        );

        $this->assertCount(1, $this->history);
        $options = $this->history[0]['options'];
        $this->assertSame(10, $options['timeout']);
    }

    public function testPerRequestTimeoutWithDifferentValues(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
            new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
        ]);
        $client = $this->createClient($mock, defaultTimeout: 30);

        // First request with timeout 5
        $client->request('GET', '/v1/test1', noAuth: true, timeout: 5);
        // Second request with timeout 120
        $client->request('GET', '/v1/test2', noAuth: true, timeout: 120);

        $this->assertCount(2, $this->history);
        $this->assertSame(5, $this->history[0]['options']['timeout']);
        $this->assertSame(120, $this->history[1]['options']['timeout']);
    }
}
