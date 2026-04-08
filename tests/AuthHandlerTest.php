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

final class AuthHandlerTest extends TestCase
{
    /** @var array<int, array{request: \Psr\Http\Message\RequestInterface}> */
    private array $history = [];

    private function createAuth(
        MockHandler $mock,
        ?string $clientSecret = 'test-secret',
        ?string $privateKey = null,
        ?string $kid = null,
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
}
