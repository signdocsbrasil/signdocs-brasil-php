<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Tests;

use PHPUnit\Framework\TestCase;
use SignDocsBrasil\Api\Errors\ApiException;
use SignDocsBrasil\Api\Errors\BadRequestException;
use SignDocsBrasil\Api\Errors\ConflictException;
use SignDocsBrasil\Api\Errors\NotFoundException;
use SignDocsBrasil\Api\Errors\ProblemDetail;
use SignDocsBrasil\Api\Errors\UnprocessableEntityException;
use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\ConfirmDocumentRequest;
use SignDocsBrasil\Api\Models\CreateTransactionRequest;
use SignDocsBrasil\Api\Models\EnrollUserRequest;
use SignDocsBrasil\Api\Models\Policy;
use SignDocsBrasil\Api\Models\PresignRequest;
use SignDocsBrasil\Api\Models\RegisterWebhookRequest;
use SignDocsBrasil\Api\Models\Signer;
use SignDocsBrasil\Api\Models\UploadDocumentRequest;

final class ResourcesTest extends TestCase
{
    private function mockHttp(): HttpClient
    {
        return $this->createMock(HttpClient::class);
    }

    private function mockTransaction(array $overrides = []): array
    {
        return array_merge([
            'tenantId' => 'ten_1',
            'transactionId' => 'tx_1',
            'status' => 'DRAFT',
            'purpose' => 'electronic_signature',
            'policy' => ['profile' => 'CLICK_ONLY'],
            'signer' => ['name' => 'Test', 'userExternalId' => 'ext_1'],
            'steps' => [],
            'expiresAt' => '2024-12-31T23:59:59Z',
            'createdAt' => '2024-01-01T00:00:00Z',
            'updatedAt' => '2024-01-01T00:00:00Z',
        ], $overrides);
    }

    public function testDocumentsUpload(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/transactions/tx_1/document', $this->anything())
            ->willReturn([
                'transactionId' => 'tx_1',
                'documentHash' => 'sha256-abc',
                'status' => 'DOCUMENT_UPLOADED',
                'uploadedAt' => '2024-01-01T00:00:00Z',
            ]);

        $docs = new \SignDocsBrasil\Api\Resources\DocumentsResource($http);
        $docs->upload('tx_1', new UploadDocumentRequest(content: 'base64data'));
    }

    public function testDocumentsPresign(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/transactions/tx_1/document/presign', $this->anything())
            ->willReturn([
                'uploadUrl' => 'https://s3/upload',
                'uploadToken' => 'tok_abc',
                's3Key' => 'uploads/doc.pdf',
                'expiresIn' => 3600,
                'contentType' => 'application/pdf',
                'instructions' => 'PUT file to uploadUrl',
            ]);

        $docs = new \SignDocsBrasil\Api\Resources\DocumentsResource($http);
        $docs->presign('tx_1', new PresignRequest(contentType: 'application/pdf', filename: 'contract.pdf'));
    }

    public function testDocumentsDownload(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/transactions/tx_1/download')
            ->willReturn([
                'transactionId' => 'tx_1',
                'documentHash' => 'sha256-abc',
                'originalUrl' => 'https://s3/original',
                'signedUrl' => 'https://s3/signed',
                'expiresIn' => 3600,
            ]);

        $docs = new \SignDocsBrasil\Api\Resources\DocumentsResource($http);
        $docs->download('tx_1');
    }

    public function testHealthCheckNoAuth(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('GET', '/health', null, null, [], true)
            ->willReturn([
                'status' => 'healthy',
                'version' => '1.0.0',
                'timestamp' => '2024-01-01T00:00:00Z',
            ]);

        $health = new \SignDocsBrasil\Api\Resources\HealthResource($http);
        $health->check();
    }

    public function testHealthHistoryNoAuth(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('GET', '/health/history', null, null, [], true)
            ->willReturn(['entries' => []]);

        $health = new \SignDocsBrasil\Api\Resources\HealthResource($http);
        $health->history();
    }

    public function testWebhooksRegister(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/webhooks', $this->anything())
            ->willReturn([
                'webhookId' => 'wh_1',
                'url' => 'https://example.com/hook',
                'secret' => 'whsec_123',
                'events' => ['TRANSACTION.COMPLETED'],
                'status' => 'ACTIVE',
                'createdAt' => '2024-01-01T00:00:00Z',
            ]);

        $webhooks = new \SignDocsBrasil\Api\Resources\WebhooksResource($http);
        $webhooks->register(new RegisterWebhookRequest(url: 'https://example.com/hook', events: ['TRANSACTION.COMPLETED']));
    }

    public function testWebhooksList(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/webhooks')
            ->willReturn([
                ['webhookId' => 'wh_1', 'url' => 'https://a.com', 'events' => [], 'status' => 'ACTIVE', 'createdAt' => '2024-01-01T00:00:00Z'],
                ['webhookId' => 'wh_2', 'url' => 'https://b.com', 'events' => [], 'status' => 'ACTIVE', 'createdAt' => '2024-01-01T00:00:00Z'],
            ]);

        $webhooks = new \SignDocsBrasil\Api\Resources\WebhooksResource($http);
        $webhooks->list();
    }

    public function testWebhooksDelete204(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('DELETE', '/v1/webhooks/wh_1')
            ->willReturn(null);

        $webhooks = new \SignDocsBrasil\Api\Resources\WebhooksResource($http);
        $webhooks->delete('wh_1');
    }

    public function testWebhooksTest(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/webhooks/wh_1/test')
            ->willReturn([
                'deliveryId' => 'dlv_1',
                'status' => 'delivered',
                'statusCode' => 200,
            ]);

        $webhooks = new \SignDocsBrasil\Api\Resources\WebhooksResource($http);
        $webhooks->test('wh_1');
    }

    public function testUsersEnrollPUT(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('PUT', '/v1/users/ext_1/enrollment', $this->anything())
            ->willReturn([
                'userExternalId' => 'ext_1',
                'enrollmentHash' => 'sha256-enroll',
                'enrollmentVersion' => 1,
                'enrollmentSource' => 'BANK_PROVIDED',
                'enrolledAt' => '2024-01-01T00:00:00Z',
                'cpf' => '12345678901',
                'faceConfidence' => 0.98,
            ]);

        $users = new \SignDocsBrasil\Api\Resources\UsersResource($http);
        $users->enroll('ext_1', new EnrollUserRequest(image: 'base64data', cpf: '12345678901'));
    }

    public function testVerificationVerifyNoAuth(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/verify/ev_1', null, null, [], true)
            ->willReturn([
                'evidenceId' => 'ev_1',
                'transactionId' => 'tx_1',
                'status' => 'COMPLETED',
                'purpose' => 'DOCUMENT_SIGNATURE',
                'signer' => ['displayName' => 'Test User'],
            ]);

        $verification = new \SignDocsBrasil\Api\Resources\VerificationResource($http);
        $verification->verify('ev_1');
    }

    public function testVerificationDownloadsNoAuth(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/verify/ev_1/downloads', null, null, [], true)
            ->willReturn([
                'evidenceId' => 'ev_1',
                'downloads' => [
                    'evidencePack' => ['url' => 'https://example.com/pack.p7m', 'expiresIn' => 3600],
                ],
            ]);

        $verification = new \SignDocsBrasil\Api\Resources\VerificationResource($http);
        $verification->downloads('ev_1');
    }

    public function testDocumentGroupsCombinedStamp(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/document-groups/grp_1/combined-stamp')
            ->willReturn([
                'groupId' => 'grp_1',
                'signerCount' => 2,
                'downloadUrl' => 'https://example.com/stamp.pdf',
                'expiresIn' => 3600,
            ]);

        $groups = new \SignDocsBrasil\Api\Resources\DocumentGroupsResource($http);
        $groups->combinedStamp('grp_1');
    }

    // ── Error path tests ────────────────────────────────────────────

    public function testTransactionsCreate400ThrowsBadRequest(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('requestWithIdempotency')
            ->willThrowException(new BadRequestException(new ProblemDetail(
                type: 'https://api.signdocs.com.br/errors/bad-request',
                title: 'Bad Request',
                status: 400,
                detail: 'Invalid policy profile',
            )));

        $resource = new \SignDocsBrasil\Api\Resources\TransactionsResource($http);

        $this->expectException(BadRequestException::class);
        $resource->create(new CreateTransactionRequest(
            purpose: 'DOCUMENT_SIGNATURE',
            policy: new Policy(profile: 'INVALID'),
            signer: new Signer(name: 'Test', userExternalId: 'ext_1'),
        ));
    }

    public function testTransactionsGet404ThrowsNotFound(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/transactions/tx-missing')
            ->willThrowException(new NotFoundException(new ProblemDetail(
                type: 'https://api.signdocs.com.br/errors/not-found',
                title: 'Not Found',
                status: 404,
                detail: 'Transaction tx-missing not found',
            )));

        $resource = new \SignDocsBrasil\Api\Resources\TransactionsResource($http);

        $this->expectException(NotFoundException::class);
        $resource->get('tx-missing');
    }

    public function testTransactionsCreate409ThrowsConflict(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('requestWithIdempotency')
            ->willThrowException(new ConflictException(new ProblemDetail(
                type: 'https://api.signdocs.com.br/errors/conflict',
                title: 'Conflict',
                status: 409,
                detail: 'Transaction already finalized',
            )));

        $resource = new \SignDocsBrasil\Api\Resources\TransactionsResource($http);

        $this->expectException(ConflictException::class);
        $resource->create(new CreateTransactionRequest(
            purpose: 'DOCUMENT_SIGNATURE',
            policy: new Policy(profile: 'CLICK_ONLY'),
            signer: new Signer(name: 'Test', userExternalId: 'ext_1'),
        ));
    }

    public function testDocumentsUpload422ThrowsUnprocessableEntity(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/transactions/tx_1/document', $this->anything())
            ->willThrowException(new UnprocessableEntityException(new ProblemDetail(
                type: 'https://api.signdocs.com.br/errors/unprocessable-entity',
                title: 'Unprocessable Entity',
                status: 422,
                detail: 'Document content is not valid base64',
            )));

        $resource = new \SignDocsBrasil\Api\Resources\DocumentsResource($http);

        $this->expectException(UnprocessableEntityException::class);
        $resource->upload('tx_1', new UploadDocumentRequest(content: 'invalid-base64!!!'));
    }

    public function testWebhooksRegister400ThrowsBadRequest(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/webhooks', $this->anything())
            ->willThrowException(new BadRequestException(new ProblemDetail(
                type: 'https://api.signdocs.com.br/errors/bad-request',
                title: 'Bad Request',
                status: 400,
                detail: 'Invalid webhook URL',
            )));

        $resource = new \SignDocsBrasil\Api\Resources\WebhooksResource($http);

        $this->expectException(BadRequestException::class);
        $resource->register(new RegisterWebhookRequest(url: 'not-a-url', events: ['TRANSACTION.COMPLETED']));
    }

    public function testDocumentsConfirm(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/v1/transactions/tx_1/document/confirm',
                ['uploadToken' => 'tok_abc123def456'],
            )
            ->willReturn([
                'transactionId' => 'tx_1',
                'status' => 'DOCUMENT_UPLOADED',
                'documentHash' => 'sha256-abc',
            ]);

        $resource = new \SignDocsBrasil\Api\Resources\DocumentsResource($http);
        $result = $resource->confirm('tx_1', new ConfirmDocumentRequest(
            uploadToken: 'tok_abc123def456',
        ));

        $this->assertSame('DOCUMENT_UPLOADED', $result->status);
    }

    // ── Pagination edge cases ───────────────────────────────────────

    public function testTransactionsListEmptyFirstPage(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/transactions', null, $this->anything())
            ->willReturn([
                'transactions' => [],
                'count' => 0,
            ]);

        $resource = new \SignDocsBrasil\Api\Resources\TransactionsResource($http);
        $result = $resource->list();

        $this->assertSame([], $result->transactions);
        $this->assertSame(0, $result->count);
        $this->assertNull($result->nextToken);
    }

    public function testTransactionsListSinglePageNoNextToken(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/transactions', null, $this->anything())
            ->willReturn([
                'transactions' => [
                    $this->mockTransaction(['transactionId' => 'tx_a']),
                    $this->mockTransaction(['transactionId' => 'tx_b']),
                ],
                'count' => 2,
            ]);

        $resource = new \SignDocsBrasil\Api\Resources\TransactionsResource($http);
        $result = $resource->list();

        $this->assertCount(2, $result->transactions);
        $this->assertSame(2, $result->count);
        $this->assertNull($result->nextToken);
        $this->assertSame('tx_a', $result->transactions[0]->transactionId);
        $this->assertSame('tx_b', $result->transactions[1]->transactionId);
    }
}
