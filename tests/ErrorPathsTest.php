<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Tests;

use PHPUnit\Framework\TestCase;
use SignDocsBrasil\Api\Errors\BadRequestException;
use SignDocsBrasil\Api\Errors\ConflictException;
use SignDocsBrasil\Api\Errors\ForbiddenException;
use SignDocsBrasil\Api\Errors\InternalServerException;
use SignDocsBrasil\Api\Errors\NotFoundException;
use SignDocsBrasil\Api\Errors\ProblemDetail;
use SignDocsBrasil\Api\Errors\RateLimitException;
use SignDocsBrasil\Api\Errors\UnauthorizedException;
use SignDocsBrasil\Api\Errors\UnprocessableEntityException;
use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\ConfirmDocumentRequest;
use SignDocsBrasil\Api\Models\CreateTransactionRequest;
use SignDocsBrasil\Api\Models\EnrollUserRequest;
use SignDocsBrasil\Api\Models\Policy;
use SignDocsBrasil\Api\Models\PresignRequest;
use SignDocsBrasil\Api\Models\Signer;
use SignDocsBrasil\Api\Models\UploadDocumentRequest;
use SignDocsBrasil\Api\Models\RegisterWebhookRequest;
use SignDocsBrasil\Api\Models\PrepareSigningRequest;
use SignDocsBrasil\Api\Models\CompleteSigningRequest;
use SignDocsBrasil\Api\Resources\DocumentGroupsResource;
use SignDocsBrasil\Api\Resources\DocumentsResource;
use SignDocsBrasil\Api\Resources\EvidenceResource;
use SignDocsBrasil\Api\Resources\SigningResource;
use SignDocsBrasil\Api\Resources\StepsResource;
use SignDocsBrasil\Api\Resources\TransactionsResource;
use SignDocsBrasil\Api\Resources\UsersResource;
use SignDocsBrasil\Api\Resources\WebhooksResource;

/**
 * Phase 3: Resource error path tests.
 * Validates that each resource correctly propagates typed exceptions
 * for all documented error status codes.
 */
final class ErrorPathsTest extends TestCase
{
    private function mockHttp(): HttpClient
    {
        return $this->createMock(HttpClient::class);
    }

    private function makeProblem(int $status, string $title, string $detail): ProblemDetail
    {
        return ProblemDetail::fromArray([
            'type' => 'https://api.signdocs.com.br/errors/' . strtolower(str_replace(' ', '-', $title)),
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'instance' => '/v1/test',
        ]);
    }

    // ── Transactions ──────────────────────────────────────────

    public function testTransactionCreate400InvalidPolicy(): void
    {
        $http = $this->mockHttp();
        $http->method('requestWithIdempotency')
            ->willThrowException(new BadRequestException(
                $this->makeProblem(400, 'Bad Request', 'Invalid policy profile: UNKNOWN_PROFILE'),
            ));

        $tx = new TransactionsResource($http);

        $this->expectException(BadRequestException::class);
        $tx->create(new CreateTransactionRequest(
            purpose: 'DOCUMENT_SIGNATURE',
            policy: new Policy(profile: 'UNKNOWN_PROFILE'),
            signer: new Signer(name: 'Test', userExternalId: 'ext_1'),
        ));
    }

    public function testTransactionGet404Nonexistent(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new NotFoundException(
                $this->makeProblem(404, 'Not Found', 'Transaction tx-nonexistent not found'),
            ));

        $tx = new TransactionsResource($http);

        $this->expectException(NotFoundException::class);
        $tx->get('tx-nonexistent');
    }

    public function testTransactionCreate409Conflict(): void
    {
        $http = $this->mockHttp();
        $http->method('requestWithIdempotency')
            ->willThrowException(new ConflictException(
                $this->makeProblem(409, 'Conflict', 'Transaction tx-uuid-001 is already finalized'),
            ));

        $tx = new TransactionsResource($http);

        $this->expectException(ConflictException::class);
        $tx->create(new CreateTransactionRequest(
            purpose: 'DOCUMENT_SIGNATURE',
            policy: new Policy(profile: 'CLICK_ONLY'),
            signer: new Signer(name: 'Test', userExternalId: 'ext_1'),
        ));
    }

    public function testTransactionFinalize409AlreadyFinalized(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new ConflictException(
                $this->makeProblem(409, 'Conflict', 'Transaction tx_1 is already finalized'),
            ));

        $tx = new TransactionsResource($http);

        $this->expectException(ConflictException::class);
        $tx->finalize('tx_1');
    }

    public function testTransactionCancel400WrongState(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new BadRequestException(
                $this->makeProblem(400, 'Bad Request', 'Transaction cannot be cancelled in current state'),
            ));

        $tx = new TransactionsResource($http);

        $this->expectException(BadRequestException::class);
        $tx->cancel('tx_1');
    }

    public function testTransactionList403InsufficientScope(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new ForbiddenException(
                $this->makeProblem(403, 'Forbidden', 'Missing required scope: transactions:read'),
            ));

        $tx = new TransactionsResource($http);

        $this->expectException(ForbiddenException::class);
        $tx->list();
    }

    public function testTransactionList429RateLimit(): void
    {
        $http = $this->mockHttp();
        $ex = new RateLimitException(
            $this->makeProblem(429, 'Too Many Requests', 'Rate limit exceeded'),
            10,
        );
        $http->method('request')->willThrowException($ex);

        $tx = new TransactionsResource($http);

        try {
            $tx->list();
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertSame(10, $e->retryAfterSeconds);
        }
    }

    public function testTransactionGet500InternalError(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new InternalServerException(
                $this->makeProblem(500, 'Internal Server Error', 'Unexpected error'),
            ));

        $tx = new TransactionsResource($http);

        $this->expectException(InternalServerException::class);
        $tx->get('tx_1');
    }

    public function testTransactionList401ExpiredToken(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new UnauthorizedException(
                $this->makeProblem(401, 'Unauthorized', 'Token expired'),
            ));

        $tx = new TransactionsResource($http);

        $this->expectException(UnauthorizedException::class);
        $tx->list();
    }

    // ── Documents ─────────────────────────────────────────────

    public function testDocumentUpload422InvalidCpf(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new UnprocessableEntityException(
                $this->makeProblem(422, 'Unprocessable Entity', 'CPF must be exactly 11 digits'),
            ));

        $docs = new DocumentsResource($http);

        $this->expectException(UnprocessableEntityException::class);
        $docs->upload('tx_1', new UploadDocumentRequest(content: 'base64'));
    }

    public function testDocumentConfirm400MissingHash(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new BadRequestException(
                $this->makeProblem(400, 'Bad Request', 'Missing sha256Hash field'),
            ));

        $docs = new DocumentsResource($http);

        $this->expectException(BadRequestException::class);
        $docs->confirm('tx_1', new ConfirmDocumentRequest(uploadToken: 'tok_abc123'));
    }

    public function testDocumentDownload404Nonexistent(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new NotFoundException(
                $this->makeProblem(404, 'Not Found', 'Transaction not found'),
            ));

        $docs = new DocumentsResource($http);

        $this->expectException(NotFoundException::class);
        $docs->download('tx-nonexistent');
    }

    public function testDocumentPresign400InvalidContentType(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new BadRequestException(
                $this->makeProblem(400, 'Bad Request', 'contentType must be application/pdf'),
            ));

        $docs = new DocumentsResource($http);

        $this->expectException(BadRequestException::class);
        $docs->presign('tx_1', new PresignRequest(contentType: 'application/pdf', filename: 'doc.pdf'));
    }

    // ── Webhooks ──────────────────────────────────────────────

    public function testWebhookRegister400HttpUrl(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new BadRequestException(
                $this->makeProblem(400, 'Bad Request', 'URL must be HTTPS'),
            ));

        $wh = new WebhooksResource($http);

        $this->expectException(BadRequestException::class);
        $wh->register(new RegisterWebhookRequest(
            url: 'http://insecure.example.com',
            events: ['TRANSACTION.COMPLETED'],
        ));
    }

    public function testWebhookDelete404Nonexistent(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new NotFoundException(
                $this->makeProblem(404, 'Not Found', 'Webhook not found'),
            ));

        $wh = new WebhooksResource($http);

        $this->expectException(NotFoundException::class);
        $wh->delete('wh-nonexistent');
    }

    public function testWebhookTest400Disabled(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new BadRequestException(
                $this->makeProblem(400, 'Bad Request', 'Webhook is disabled'),
            ));

        $wh = new WebhooksResource($http);

        $this->expectException(BadRequestException::class);
        $wh->test('wh_1');
    }

    // ── Steps ─────────────────────────────────────────────────

    public function testStepStart404Nonexistent(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new NotFoundException(
                $this->makeProblem(404, 'Not Found', 'Step step-nonexistent not found'),
            ));

        $steps = new StepsResource($http);

        $this->expectException(NotFoundException::class);
        $steps->start('tx_1', 'step-nonexistent');
    }

    public function testStepComplete409AlreadyCompleted(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new ConflictException(
                $this->makeProblem(409, 'Conflict', 'Step is already completed'),
            ));

        $steps = new StepsResource($http);

        $this->expectException(ConflictException::class);
        $steps->complete('tx_1', 'step_1', ['accepted' => true]);
    }

    public function testStepComplete422WrongOtp(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new UnprocessableEntityException(
                $this->makeProblem(422, 'Unprocessable Entity', 'Invalid OTP code'),
            ));

        $steps = new StepsResource($http);

        $this->expectException(UnprocessableEntityException::class);
        $steps->complete('tx_1', 'step_1', ['code' => '000000']);
    }

    // ── Signing ───────────────────────────────────────────────

    public function testSigningPrepare400EmptyCertChain(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new BadRequestException(
                $this->makeProblem(400, 'Bad Request', 'certificateChainPems must not be empty'),
            ));

        $signing = new SigningResource($http);

        $this->expectException(BadRequestException::class);
        $signing->prepare('tx_1', new PrepareSigningRequest(certificateChainPems: []));
    }

    public function testSigningComplete422InvalidSignature(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new UnprocessableEntityException(
                $this->makeProblem(422, 'Unprocessable Entity', 'Invalid raw signature'),
            ));

        $signing = new SigningResource($http);

        $this->expectException(UnprocessableEntityException::class);
        $signing->complete('tx_1', new CompleteSigningRequest(
            signatureRequestId: 'req_1',
            rawSignatureBase64: 'invalid',
        ));
    }

    // ── Evidence ──────────────────────────────────────────────

    public function testEvidenceGet404NoEvidence(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new NotFoundException(
                $this->makeProblem(404, 'Not Found', 'Evidence not found for transaction tx_1'),
            ));

        $ev = new EvidenceResource($http);

        $this->expectException(NotFoundException::class);
        $ev->get('tx_1');
    }

    public function testEvidenceGet403MissingScope(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new ForbiddenException(
                $this->makeProblem(403, 'Forbidden', 'Missing required scope: evidence:read'),
            ));

        $ev = new EvidenceResource($http);

        $this->expectException(ForbiddenException::class);
        $ev->get('tx_1');
    }

    // ── Users ─────────────────────────────────────────────────

    public function testUserEnroll422InvalidImage(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new UnprocessableEntityException(
                $this->makeProblem(422, 'Unprocessable Entity', 'Image must be a valid JPEG base64'),
            ));

        $users = new UsersResource($http);

        $this->expectException(UnprocessableEntityException::class);
        $users->enroll('usr_1', new EnrollUserRequest(
            image: 'not-base64',
            cpf: '12345678901',
        ));
    }

    // ── Document Groups ───────────────────────────────────────

    public function testDocumentGroupCombinedStamp404Nonexistent(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new NotFoundException(
                $this->makeProblem(404, 'Not Found', 'Document group not found'),
            ));

        $grp = new DocumentGroupsResource($http);

        $this->expectException(NotFoundException::class);
        $grp->combinedStamp('grp-nonexistent');
    }

    public function testDocumentGroupCombinedStamp409NotFullySigned(): void
    {
        $http = $this->mockHttp();
        $http->method('request')
            ->willThrowException(new ConflictException(
                $this->makeProblem(409, 'Conflict', 'Not all signers have completed signing'),
            ));

        $grp = new DocumentGroupsResource($http);

        $this->expectException(ConflictException::class);
        $grp->combinedStamp('grp_1');
    }
}
