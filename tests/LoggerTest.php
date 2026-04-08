<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use SignDocsBrasil\Api\AuthHandler;
use SignDocsBrasil\Api\Config;
use SignDocsBrasil\Api\Errors\BadRequestException;
use SignDocsBrasil\Api\HttpClient;

/**
 * Simple in-memory PSR-3 logger for testing.
 */
final class TestLogger extends AbstractLogger
{
    /** @var array<int, array{level: string, message: string, context: array<string, mixed>}> */
    public array $records = [];

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level' => (string) $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }

    /**
     * @return array<int, array{level: string, message: string, context: array<string, mixed>}>
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    public function hasInfoRecord(string $message): bool
    {
        foreach ($this->records as $record) {
            if ($record['level'] === 'info' && $record['message'] === $message) {
                return true;
            }
        }
        return false;
    }

    public function hasWarningRecord(string $message): bool
    {
        foreach ($this->records as $record) {
            if ($record['level'] === 'warning' && $record['message'] === $message) {
                return true;
            }
        }
        return false;
    }
}

final class LoggerTest extends TestCase
{
    private TestLogger $logger;

    /** @var array<int, array{request: \Psr\Http\Message\RequestInterface}> */
    private array $history = [];

    protected function setUp(): void
    {
        $this->logger = new TestLogger();
    }

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

    private function createClient(MockHandler $mock, ?LoggerInterface $logger = null): HttpClient
    {
        $this->history = [];
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push(Middleware::history($this->history));
        $guzzle = new GuzzleClient(['handler' => $handlerStack, 'http_errors' => false]);

        return new HttpClient(
            baseUrl: 'https://api.signdocs.com.br',
            timeout: 30,
            auth: $this->createAuthHandler(),
            maxRetries: 0,
            guzzle: $guzzle,
            logger: $logger,
        );
    }

    public function testLoggerReceivesInfoOnSuccess(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
        ]);
        $client = $this->createClient($mock, $this->logger);

        $client->request('GET', '/v1/test', noAuth: true);

        $this->assertTrue($this->logger->hasInfoRecord('request completed'));

        $record = $this->logger->getRecords()[0];
        $this->assertSame('info', $record['level']);
        $this->assertSame('request completed', $record['message']);
        $this->assertSame('GET', $record['context']['method']);
        $this->assertSame('/v1/test', $record['context']['path']);
        $this->assertSame(200, $record['context']['status']);
        $this->assertArrayHasKey('duration_ms', $record['context']);
        $this->assertIsInt($record['context']['duration_ms']);
    }

    public function testLoggerReceivesWarningOn4xx(): void
    {
        $mock = new MockHandler([
            new Response(400, ['Content-Type' => 'application/json'], json_encode([
                'type' => 'about:blank', 'title' => 'Bad Request', 'status' => 400,
            ])),
        ]);
        $client = $this->createClient($mock, $this->logger);

        try {
            $client->request('POST', '/v1/transactions');
        } catch (BadRequestException) {
            // Expected
        }

        $this->assertTrue($this->logger->hasWarningRecord('request failed'));

        $record = $this->logger->getRecords()[0];
        $this->assertSame('warning', $record['level']);
        $this->assertSame('request failed', $record['message']);
        $this->assertSame('POST', $record['context']['method']);
        $this->assertSame('/v1/transactions', $record['context']['path']);
        $this->assertSame(400, $record['context']['status']);
        $this->assertArrayHasKey('duration_ms', $record['context']);
    }

    public function testLoggerDoesNotIncludeAuthorizationHeaders(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"tx_1"}'),
        ]);
        $client = $this->createClient($mock, $this->logger);

        $client->request('GET', '/v1/transactions/tx_1');

        $records = $this->logger->getRecords();
        $this->assertNotEmpty($records);

        foreach ($records as $record) {
            $context = $record['context'];
            // Ensure no Authorization header, token, or body content is logged
            $this->assertArrayNotHasKey('headers', $context);
            $this->assertArrayNotHasKey('authorization', $context);
            $this->assertArrayNotHasKey('Authorization', $context);
            $this->assertArrayNotHasKey('token', $context);
            $this->assertArrayNotHasKey('body', $context);

            // Check that the context values don't contain any token strings
            $contextJson = json_encode($context);
            $this->assertStringNotContainsString('test-token', $contextJson);
            $this->assertStringNotContainsString('Bearer', $contextJson);
        }
    }

    public function testNullLoggerDoesNotCauseIssues(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
        ]);

        // Pass null logger (default)
        $client = $this->createClient($mock, null);

        // Should not throw any errors
        $result = $client->request('GET', '/v1/test', noAuth: true);
        $this->assertSame(['ok' => true], $result);
    }

    public function testNullLoggerDoesNotCauseIssuesOnError(): void
    {
        $mock = new MockHandler([
            new Response(400, ['Content-Type' => 'application/json'], json_encode([
                'type' => 'about:blank', 'title' => 'Bad Request', 'status' => 400,
            ])),
        ]);

        $client = $this->createClient($mock, null);

        $this->expectException(BadRequestException::class);
        $client->request('POST', '/v1/test');
    }

    public function testLoggerContextIncludesCorrectMethod(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"tx_1"}'),
        ]);
        $client = $this->createClient($mock, $this->logger);

        $client->request('POST', '/v1/transactions', body: ['name' => 'test']);

        $record = $this->logger->getRecords()[0];
        $this->assertSame('POST', $record['context']['method']);
    }

    public function testLoggerContextIncludesCorrectPath(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], '{"id":"tx_1"}'),
        ]);
        $client = $this->createClient($mock, $this->logger);

        $client->request('GET', '/v1/transactions/abc123');

        $record = $this->logger->getRecords()[0];
        $this->assertSame('/v1/transactions/abc123', $record['context']['path']);
    }

    public function testLoggerViaConfig(): void
    {
        $config = new Config(
            clientId: 'test-client',
            clientSecret: 'test-secret',
            logger: $this->logger,
        );

        $this->assertSame($this->logger, $config->logger);
    }

    public function testConfigDefaultLoggerIsNull(): void
    {
        $config = new Config(
            clientId: 'test-client',
            clientSecret: 'test-secret',
        );

        $this->assertNull($config->logger);
    }

    public function test204ResponseLogsInfo(): void
    {
        $mock = new MockHandler([
            new Response(204),
        ]);
        $client = $this->createClient($mock, $this->logger);

        $result = $client->request('DELETE', '/v1/webhooks/wh_1', noAuth: true);

        $this->assertNull($result);
        $this->assertTrue($this->logger->hasInfoRecord('request completed'));

        $record = $this->logger->getRecords()[0];
        $this->assertSame(204, $record['context']['status']);
    }
}
