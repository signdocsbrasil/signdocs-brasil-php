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
use SignDocsBrasil\Api\Errors\ConflictException;
use SignDocsBrasil\Api\Errors\NotFoundException;
use SignDocsBrasil\Api\Errors\RateLimitException;
use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\CreateTransactionRequest;
use SignDocsBrasil\Api\Models\Policy;
use SignDocsBrasil\Api\Models\RegisterWebhookRequest;
use SignDocsBrasil\Api\Models\Signer;
use SignDocsBrasil\Api\Models\UploadDocumentRequest;
use SignDocsBrasil\Api\Resources\DocumentsResource;
use SignDocsBrasil\Api\Resources\HealthResource;
use SignDocsBrasil\Api\Resources\TransactionsResource;
use SignDocsBrasil\Api\Resources\VerificationResource;
use SignDocsBrasil\Api\Resources\WebhooksResource;

/**
 * Integration tests that exercise the full stack:
 * Config -> AuthHandler -> HttpClient -> RetryHandler -> Resource
 *
 * Uses Guzzle MockHandler to simulate HTTP responses with fixture data.
 */
final class IntegrationTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__ . '/../fixtures';

    /** @var array<int, array{request: \Psr\Http\Message\RequestInterface}> */
    private array $apiHistory = [];

    /** @var array<int, array{request: \Psr\Http\Message\RequestInterface}> */
    private array $authHistory = [];

    /**
     * Load a fixture file and return the decoded JSON.
     *
     * @return array<string, mixed>
     */
    private function loadFixture(string $name): array
    {
        $path = self::FIXTURES_DIR . '/' . $name;
        $content = file_get_contents($path);
        assert($content !== false, "Fixture file not found: {$path}");

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Create a token response for the auth mock.
     */
    private function tokenResponse(): Response
    {
        $fixture = $this->loadFixture('token-client-secret.json');
        $body = $fixture['response']['body'];

        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode($body),
        );
    }

    /**
     * Build the full integration stack with mocked HTTP layers.
     *
     * @param Response[] $apiResponses  Queued responses for the API Guzzle client
     * @param bool       $includeAuth   Whether to queue a token response for auth
     * @return HttpClient
     */
    private function buildStack(array $apiResponses, bool $includeAuth = true): HttpClient
    {
        // --- Auth Guzzle mock ---
        $this->authHistory = [];
        $authResponses = $includeAuth ? [$this->tokenResponse()] : [];
        $authMock = new MockHandler($authResponses);
        $authStack = HandlerStack::create($authMock);
        $authStack->push(Middleware::history($this->authHistory));
        $authGuzzle = new GuzzleClient(['handler' => $authStack]);

        $auth = new AuthHandler(
            clientId: 'tenant_abc123_cid001',
            clientSecret: 'sk_test_supersecret',
            baseUrl: 'https://api.signdocs.com.br',
            scopes: ['transactions:read', 'transactions:write'],
            guzzle: $authGuzzle,
        );

        // --- API Guzzle mock ---
        $this->apiHistory = [];
        $apiMock = new MockHandler($apiResponses);
        $apiStack = HandlerStack::create($apiMock);
        $apiStack->push(Middleware::history($this->apiHistory));
        $apiGuzzle = new GuzzleClient(['handler' => $apiStack, 'http_errors' => false]);

        return new HttpClient(
            baseUrl: 'https://api.signdocs.com.br',
            timeout: 30,
            auth: $auth,
            maxRetries: 0,
            guzzle: $apiGuzzle,
        );
    }

    /**
     * Build an API response from a fixture file.
     */
    private function fixtureResponse(string $fixtureName): Response
    {
        $fixture = $this->loadFixture($fixtureName);
        $response = $fixture['response'];
        $status = $response['status'];
        $headers = $response['headers'] ?? ['Content-Type' => 'application/json'];
        $body = json_encode($response['body']);

        return new Response($status, $headers, $body);
    }

    // ---------------------------------------------------------------
    // Happy path: transactions()->create()
    // ---------------------------------------------------------------

    public function testTransactionsCreate(): void
    {
        $http = $this->buildStack([$this->fixtureResponse('transactions-create.json')]);
        $resource = new TransactionsResource($http);

        $request = new CreateTransactionRequest(
            purpose: 'DOCUMENT_SIGNATURE',
            policy: new Policy(profile: 'CLICK_ONLY'),
            signer: new Signer(
                name: 'João Silva',
                userExternalId: 'user-ext-001',
                email: 'joao@example.com',
                cpf: '12345678901',
            ),
            metadata: ['contractId' => 'CTR-2024-001'],
        );

        $tx = $resource->create($request);

        $this->assertSame('abc123', $tx->tenantId);
        $this->assertSame('tx-uuid-001', $tx->transactionId);
        $this->assertSame('CREATED', $tx->status);
        $this->assertSame('DOCUMENT_SIGNATURE', $tx->purpose);
        $this->assertSame('CLICK_ONLY', $tx->policy->profile);
        $this->assertSame('João Silva', $tx->signer->name);
        $this->assertSame('joao@example.com', $tx->signer->email);
        $this->assertSame('user-ext-001', $tx->signer->userExternalId);
        $this->assertSame('12345678901', $tx->signer->cpf);
        $this->assertCount(1, $tx->steps);
        $this->assertSame('step-uuid-001', $tx->steps[0]->stepId);
        $this->assertSame('CLICK_ACCEPT', $tx->steps[0]->type);
        $this->assertSame('PENDING', $tx->steps[0]->status);
        $this->assertSame(1, $tx->steps[0]->order);
        $this->assertSame(0, $tx->steps[0]->attempts);
        $this->assertSame(3, $tx->steps[0]->maxAttempts);
        $this->assertSame(['contractId' => 'CTR-2024-001'], $tx->metadata);
        $this->assertSame('2024-11-16T00:00:00.000Z', $tx->expiresAt);
        $this->assertSame('2024-11-15T00:00:00.000Z', $tx->createdAt);
        $this->assertSame('2024-11-15T00:00:00.000Z', $tx->updatedAt);

        // Verify auth was called
        $this->assertCount(1, $this->authHistory);
        // Verify API request was made with auth header
        $this->assertCount(1, $this->apiHistory);
        $apiReq = $this->apiHistory[0]['request'];
        $this->assertStringStartsWith('Bearer ', $apiReq->getHeaderLine('Authorization'));
        $this->assertTrue($apiReq->hasHeader('X-Idempotency-Key'));
    }

    // ---------------------------------------------------------------
    // Happy path: transactions()->list()
    // ---------------------------------------------------------------

    public function testTransactionsList(): void
    {
        $http = $this->buildStack([$this->fixtureResponse('transactions-list.json')]);
        $resource = new TransactionsResource($http);

        $result = $resource->list();

        $this->assertSame(2, $result->count);
        $this->assertCount(2, $result->transactions);
        $this->assertSame(
            'eyJQSyI6IlRFTkFOVCNhYmMxMjMiLCJTSyI6IlRYI3R4LXV1aWQtMDAzIn0=',
            $result->nextToken,
        );

        $first = $result->transactions[0];
        $this->assertSame('tx-uuid-002', $first->transactionId);
        $this->assertSame('COMPLETED', $first->status);
        $this->assertSame('Maria Santos', $first->signer->name);

        $second = $result->transactions[1];
        $this->assertSame('tx-uuid-003', $second->transactionId);
        $this->assertSame('BIOMETRIC', $second->policy->profile);
        $this->assertSame('Pedro Costa', $second->signer->name);
    }

    // ---------------------------------------------------------------
    // Happy path: transactions()->get() with nested steps
    // ---------------------------------------------------------------

    public function testTransactionsGet(): void
    {
        $http = $this->buildStack([$this->fixtureResponse('transactions-get.json')]);
        $resource = new TransactionsResource($http);

        $tx = $resource->get('tx-uuid-001');

        $this->assertSame('tx-uuid-001', $tx->transactionId);
        $this->assertSame('IN_PROGRESS', $tx->status);
        $this->assertSame('CLICK_PLUS_OTP', $tx->policy->profile);
        $this->assertCount(2, $tx->steps);

        // First step: completed
        $step1 = $tx->steps[0];
        $this->assertSame('step-uuid-001', $step1->stepId);
        $this->assertSame('CLICK_ACCEPT', $step1->type);
        $this->assertSame('COMPLETED', $step1->status);
        $this->assertSame(1, $step1->order);
        $this->assertSame(1, $step1->attempts);
        $this->assertSame('2024-11-15T00:01:00.000Z', $step1->completedAt);
        $this->assertNotNull($step1->result);

        // Second step: pending
        $step2 = $tx->steps[1];
        $this->assertSame('step-uuid-002', $step2->stepId);
        $this->assertSame('OTP_CHALLENGE', $step2->type);
        $this->assertSame('PENDING', $step2->status);
        $this->assertSame(2, $step2->order);
        $this->assertSame(0, $step2->attempts);
        $this->assertNull($step2->completedAt);
    }

    // ---------------------------------------------------------------
    // Happy path: documents()->upload()
    // ---------------------------------------------------------------

    public function testDocumentsUpload(): void
    {
        $http = $this->buildStack([$this->fixtureResponse('documents-upload.json')]);
        $resource = new DocumentsResource($http);

        $request = new UploadDocumentRequest(
            content: 'JVBERi0xLjQKMSAwIG9iago8PAovVHlwZQ==',
            filename: 'contract.pdf',
        );

        $result = $resource->upload('tx-uuid-001', $request);

        $this->assertSame('tx-uuid-001', $result->transactionId);
        $this->assertSame('DOCUMENT_UPLOADED', $result->status);
        $this->assertSame('sha256-abc123def456', $result->documentHash);
        $this->assertSame('2024-11-15T00:00:30.000Z', $result->uploadedAt);
    }

    // ---------------------------------------------------------------
    // Happy path: webhooks()->register()
    // ---------------------------------------------------------------

    public function testWebhooksRegister(): void
    {
        $http = $this->buildStack([$this->fixtureResponse('webhooks-register.json')]);
        $resource = new WebhooksResource($http);

        $request = new RegisterWebhookRequest(
            url: 'https://example.com/webhooks/signdocs',
            events: ['TRANSACTION.COMPLETED', 'TRANSACTION.FAILED'],
        );

        $result = $resource->register($request);

        $this->assertSame('wh-uuid-001', $result->webhookId);
        $this->assertSame('https://example.com/webhooks/signdocs', $result->url);
        $this->assertSame('whsec_generated_secret_abc123', $result->secret);
        $this->assertSame(['TRANSACTION.COMPLETED', 'TRANSACTION.FAILED'], $result->events);
        $this->assertSame('ACTIVE', $result->status);
        $this->assertSame('2024-11-15T00:00:00.000Z', $result->createdAt);
    }

    // ---------------------------------------------------------------
    // Happy path: health()->check() (no auth)
    // ---------------------------------------------------------------

    public function testHealthCheck(): void
    {
        $http = $this->buildStack(
            [$this->fixtureResponse('health-check.json')],
            includeAuth: false,
        );
        $resource = new HealthResource($http);

        $result = $resource->check();

        $this->assertSame('healthy', $result->status);
        $this->assertSame('1.0.0', $result->version);
        $this->assertSame('2024-11-15T12:00:00.000Z', $result->timestamp);
        $this->assertIsArray($result->services);
        $this->assertSame('healthy', $result->services['dynamodb']['status']);
        $this->assertSame(12, $result->services['dynamodb']['latency']);
        $this->assertSame('healthy', $result->services['s3']['status']);
        $this->assertSame('healthy', $result->services['cognito']['status']);

        // No auth should have been requested
        $this->assertCount(0, $this->authHistory);
        // API request should not have Authorization header
        $apiReq = $this->apiHistory[0]['request'];
        $this->assertFalse($apiReq->hasHeader('Authorization'));
    }

    // ---------------------------------------------------------------
    // Happy path: verification()->verify() (no auth)
    // ---------------------------------------------------------------

    public function testVerificationVerify(): void
    {
        $http = $this->buildStack(
            [$this->fixtureResponse('verification-verify.json')],
            includeAuth: false,
        );
        $resource = new VerificationResource($http);

        $result = $resource->verify('ev-uuid-001');

        $this->assertSame('ev-uuid-001', $result->evidenceId);
        $this->assertSame('tx-uuid-001', $result->transactionId);
        $this->assertSame('COMPLETED', $result->status);
        $this->assertSame('DOCUMENT_SIGNATURE', $result->purpose);
        $this->assertSame('sha256-doc-hash', $result->documentHash);
        $this->assertSame('sha256-evidence-hash', $result->evidenceHash);
        $this->assertSame(['displayName' => 'João Silva'], $result->signer);
        $this->assertSame('Acme Corp', $result->tenantName);
        $this->assertSame('2024-11-15T00:01:00.000Z', $result->completedAt);

        // No auth should have been requested
        $this->assertCount(0, $this->authHistory);
        $apiReq = $this->apiHistory[0]['request'];
        $this->assertFalse($apiReq->hasHeader('Authorization'));
    }

    // ---------------------------------------------------------------
    // Error path: 400 Bad Request
    // ---------------------------------------------------------------

    public function testTransactionsCreate400ThrowsBadRequest(): void
    {
        $http = $this->buildStack([$this->fixtureResponse('error-400.json')]);
        $resource = new TransactionsResource($http);

        $request = new CreateTransactionRequest(
            purpose: 'DOCUMENT_SIGNATURE',
            policy: new Policy(profile: 'UNKNOWN_PROFILE'),
            signer: new Signer(name: 'Test', userExternalId: 'ext_1'),
        );

        try {
            $resource->create($request);
            $this->fail('Expected BadRequestException to be thrown');
        } catch (BadRequestException $e) {
            $this->assertSame(400, $e->getStatus());
            $this->assertSame('https://api.signdocs.com.br/errors/bad-request', $e->getType());
            $this->assertSame('Bad Request', $e->getTitle());
            $this->assertSame('Invalid policy profile: UNKNOWN_PROFILE', $e->getDetail());
            $this->assertSame('/v1/transactions', $e->getInstance());
        }
    }

    // ---------------------------------------------------------------
    // Error path: 404 Not Found
    // ---------------------------------------------------------------

    public function testTransactionsGet404ThrowsNotFound(): void
    {
        $http = $this->buildStack([$this->fixtureResponse('error-404.json')]);
        $resource = new TransactionsResource($http);

        try {
            $resource->get('tx-nonexistent');
            $this->fail('Expected NotFoundException to be thrown');
        } catch (NotFoundException $e) {
            $this->assertSame(404, $e->getStatus());
            $this->assertSame('https://api.signdocs.com.br/errors/not-found', $e->getType());
            $this->assertSame('Not Found', $e->getTitle());
            $this->assertSame('Transaction tx-nonexistent not found', $e->getDetail());
        }
    }

    // ---------------------------------------------------------------
    // Error path: 429 Rate Limit with Retry-After
    // ---------------------------------------------------------------

    public function testTransactionsCreate429ThrowsRateLimit(): void
    {
        $http = $this->buildStack([$this->fixtureResponse('error-429.json')]);
        $resource = new TransactionsResource($http);

        $request = new CreateTransactionRequest(
            purpose: 'DOCUMENT_SIGNATURE',
            policy: new Policy(profile: 'CLICK_ONLY'),
            signer: new Signer(name: 'Test', userExternalId: 'ext_1'),
        );

        try {
            $resource->create($request);
            $this->fail('Expected RateLimitException to be thrown');
        } catch (RateLimitException $e) {
            $this->assertSame(429, $e->getStatus());
            $this->assertSame('https://api.signdocs.com.br/errors/rate-limit', $e->getType());
            $this->assertSame('Too Many Requests', $e->getTitle());
            $this->assertSame('Daily transaction quota exceeded', $e->getDetail());
            $this->assertSame(5, $e->retryAfterSeconds);
        }
    }

    // ---------------------------------------------------------------
    // Error path: 409 Conflict
    // ---------------------------------------------------------------

    public function testTransactionsCreate409ThrowsConflict(): void
    {
        $http = $this->buildStack([$this->fixtureResponse('error-409.json')]);
        $resource = new TransactionsResource($http);

        $request = new CreateTransactionRequest(
            purpose: 'DOCUMENT_SIGNATURE',
            policy: new Policy(profile: 'CLICK_ONLY'),
            signer: new Signer(name: 'Test', userExternalId: 'ext_1'),
        );

        try {
            $resource->create($request);
            $this->fail('Expected ConflictException to be thrown');
        } catch (ConflictException $e) {
            $this->assertSame(409, $e->getStatus());
            $this->assertSame('https://api.signdocs.com.br/errors/conflict', $e->getType());
            $this->assertSame('Conflict', $e->getTitle());
            $this->assertSame('Transaction tx-uuid-001 is already finalized', $e->getDetail());
        }
    }
}
