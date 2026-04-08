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
use SignDocsBrasil\Api\Config;
use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Resources\HealthResource;
use SignDocsBrasil\Api\SignDocsBrasilClient;

final class CustomGuzzleTest extends TestCase
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
        // Pre-warm token cache
        $auth->getAccessToken();

        return $auth;
    }

    private function createCustomGuzzle(MockHandler $mock): GuzzleClient
    {
        $this->history = [];
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($this->history));

        return new GuzzleClient([
            'handler' => $handlerStack,
            'http_errors' => false,
        ]);
    }

    public function testCustomGuzzleClientIsUsedForRequests(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"status":"healthy"}'),
        ]);

        $customGuzzle = $this->createCustomGuzzle($mock);
        $auth = $this->createAuthHandler();

        $http = new HttpClient(
            baseUrl: 'https://api.signdocs.com.br',
            timeout: 30,
            auth: $auth,
            maxRetries: 0,
            guzzle: $customGuzzle,
        );

        $http->request('GET', '/health', noAuth: true);

        // Verify the request went through our custom Guzzle (captured in history)
        $this->assertCount(1, $this->history);
        $request = $this->history[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertStringContainsString('health', $request->getUri()->getPath());
    }

    public function testCustomGuzzleViaConfig(): void
    {
        $mock = new MockHandler([
            // Token request
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'access_token' => 'config-test-token',
                'expires_in' => 3600,
            ])),
            // Health check request
            new Response(200, ['Content-Type' => 'application/json'], '{"status":"healthy"}'),
        ]);

        $customGuzzle = $this->createCustomGuzzle($mock);

        $config = new Config(
            clientId: 'test-client',
            clientSecret: 'test-secret',
            guzzle: $customGuzzle,
        );

        // Verify the Config stores the custom Guzzle
        $this->assertSame($customGuzzle, $config->guzzle);
    }

    public function testCustomGuzzleReceivesAuthHeaders(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"tx_1"}'),
        ]);

        $customGuzzle = $this->createCustomGuzzle($mock);
        $auth = $this->createAuthHandler();

        $http = new HttpClient(
            baseUrl: 'https://api.signdocs.com.br',
            timeout: 30,
            auth: $auth,
            maxRetries: 0,
            guzzle: $customGuzzle,
        );

        $http->request('GET', '/v1/transactions/tx_1');

        $this->assertCount(1, $this->history);
        $request = $this->history[0]['request'];
        $this->assertStringStartsWith('Bearer ', $request->getHeaderLine('Authorization'));
    }

    public function testCustomGuzzleReceivesJsonBody(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"tx_1"}'),
        ]);

        $customGuzzle = $this->createCustomGuzzle($mock);
        $auth = $this->createAuthHandler();

        $http = new HttpClient(
            baseUrl: 'https://api.signdocs.com.br',
            timeout: 30,
            auth: $auth,
            maxRetries: 0,
            guzzle: $customGuzzle,
        );

        $http->request('POST', '/v1/transactions', body: ['name' => 'test']);

        $this->assertCount(1, $this->history);
        $request = $this->history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertStringContainsString('application/json', $request->getHeaderLine('Content-Type'));
    }

    public function testDefaultGuzzleCreatedWhenNoneProvided(): void
    {
        // When no custom Guzzle is provided, HttpClient should create its own.
        // We verify this indirectly: Config with null guzzle is the default.
        $config = new Config(
            clientId: 'test-client',
            clientSecret: 'test-secret',
        );

        $this->assertNull($config->guzzle);
    }
}
