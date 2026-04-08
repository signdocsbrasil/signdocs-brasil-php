<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Tests;

use PHPUnit\Framework\TestCase;
use SignDocsBrasil\Api\Errors\ProblemDetail;
use SignDocsBrasil\Api\Models\CombinedStampResponse;
use SignDocsBrasil\Api\Models\CompleteSigningRequest;
use SignDocsBrasil\Api\Models\CompleteSigningResponse;
use SignDocsBrasil\Api\Models\ConfirmDocumentRequest;
use SignDocsBrasil\Api\Models\CreateTransactionRequest;
use SignDocsBrasil\Api\Models\DownloadResponse;
use SignDocsBrasil\Api\Models\EnrollUserRequest;
use SignDocsBrasil\Api\Models\EnrollUserResponse;
use SignDocsBrasil\Api\Models\Evidence;
use SignDocsBrasil\Api\Models\HealthCheckResponse;
use SignDocsBrasil\Api\Models\HealthHistoryResponse;
use SignDocsBrasil\Api\Models\Policy;
use SignDocsBrasil\Api\Models\PrepareSigningRequest;
use SignDocsBrasil\Api\Models\PrepareSigningResponse;
use SignDocsBrasil\Api\Models\PresignResponse;
use SignDocsBrasil\Api\Models\RegisterWebhookRequest;
use SignDocsBrasil\Api\Models\RegisterWebhookResponse;
use SignDocsBrasil\Api\Models\Signer;
use SignDocsBrasil\Api\Models\StartStepRequest;
use SignDocsBrasil\Api\Models\StartStepResponse;
use SignDocsBrasil\Api\Models\Step;
use SignDocsBrasil\Api\Models\StepResult;
use SignDocsBrasil\Api\Models\Transaction;
use SignDocsBrasil\Api\Models\TransactionListParams;
use SignDocsBrasil\Api\Models\TransactionListResponse;
use SignDocsBrasil\Api\Models\UploadDocumentRequest;
use SignDocsBrasil\Api\Models\VerificationDownloadsResponse;
use SignDocsBrasil\Api\Models\VerificationResponse;
use SignDocsBrasil\Api\Models\Webhook;
use SignDocsBrasil\Api\Models\WebhookTestResponse;

final class ModelsTest extends TestCase
{
    private const FIXTURES_DIR = __DIR__ . '/../fixtures';

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

    // ── Policy ──────────────────────────────────────────────────────

    public function testPolicyFromArrayMinimal(): void
    {
        $policy = Policy::fromArray(['profile' => 'CLICK_ONLY']);

        $this->assertSame('CLICK_ONLY', $policy->profile);
        $this->assertNull($policy->customSteps);
    }

    public function testPolicyWithCustomSteps(): void
    {
        $policy = Policy::fromArray([
            'profile' => 'CUSTOM',
            'customSteps' => ['LIVENESS', 'OTP'],
        ]);

        $this->assertSame('CUSTOM', $policy->profile);
        $this->assertSame(['LIVENESS', 'OTP'], $policy->customSteps);
    }

    public function testPolicyRoundtrip(): void
    {
        $data = ['profile' => 'BIOMETRIC', 'customSteps' => ['LIVENESS']];
        $policy = Policy::fromArray($data);
        $this->assertSame($data, $policy->toArray());
    }

    public function testPolicyToArrayOmitsNullCustomSteps(): void
    {
        $policy = new Policy(profile: 'CLICK_ONLY');
        $arr = $policy->toArray();
        $this->assertArrayNotHasKey('customSteps', $arr);
    }

    // ── Signer ──────────────────────────────────────────────────────

    public function testSignerFromArrayFull(): void
    {
        $signer = Signer::fromArray([
            'name' => 'Joao Silva',
            'userExternalId' => 'ext_123',
            'email' => 'joao@example.com',
            'displayName' => 'Joao',
            'cpf' => '12345678901',
            'cnpj' => '12345678000199',
        ]);

        $this->assertSame('Joao Silva', $signer->name);
        $this->assertSame('ext_123', $signer->userExternalId);
        $this->assertSame('joao@example.com', $signer->email);
        $this->assertSame('Joao', $signer->displayName);
        $this->assertSame('12345678901', $signer->cpf);
        $this->assertSame('12345678000199', $signer->cnpj);
    }

    public function testSignerFromArrayMinimal(): void
    {
        $signer = Signer::fromArray([
            'name' => 'Maria',
            'userExternalId' => 'ext_456',
        ]);

        $this->assertSame('Maria', $signer->name);
        $this->assertNull($signer->email);
        $this->assertNull($signer->cpf);
        $this->assertNull($signer->cnpj);
    }

    public function testSignerToArrayOmitsNulls(): void
    {
        $signer = new Signer(name: 'Test', userExternalId: 'ext_1');
        $arr = $signer->toArray();

        $this->assertSame(['name' => 'Test', 'userExternalId' => 'ext_1'], $arr);
    }

    public function testSignerRoundtrip(): void
    {
        $data = [
            'name' => 'Joao',
            'userExternalId' => 'ext_1',
            'email' => 'joao@test.com',
            'cpf' => '11122233344',
        ];
        $signer = Signer::fromArray($data);
        $this->assertSame($data, $signer->toArray());
    }

    // ── StepResult ──────────────────────────────────────────────────

    public function testStepResultFromArrayEmpty(): void
    {
        $result = StepResult::fromArray([]);

        $this->assertNull($result->liveness);
        $this->assertNull($result->match);
        $this->assertNull($result->otp);
        $this->assertNull($result->click);
        $this->assertNull($result->digitalSignature);
        $this->assertNull($result->providerTimestamp);
    }

    public function testStepResultFromArrayWithData(): void
    {
        $result = StepResult::fromArray([
            'liveness' => ['score' => 0.95],
            'providerTimestamp' => '2025-01-01T00:00:00Z',
        ]);

        $this->assertSame(['score' => 0.95], $result->liveness);
        $this->assertSame('2025-01-01T00:00:00Z', $result->providerTimestamp);
    }

    public function testStepResultRoundtrip(): void
    {
        $data = [
            'click' => ['ip' => '127.0.0.1'],
            'providerTimestamp' => '2025-06-01T12:00:00Z',
        ];
        $result = StepResult::fromArray($data);
        $this->assertSame($data, $result->toArray());
    }

    // ── Step ────────────────────────────────────────────────────────

    public function testStepFromArrayFull(): void
    {
        $step = Step::fromArray([
            'tenantId' => 'tenant_1',
            'transactionId' => 'tx_1',
            'stepId' => 'step_1',
            'type' => 'CLICK',
            'status' => 'COMPLETED',
            'order' => 1,
            'attempts' => 1,
            'maxAttempts' => 3,
            'captureMode' => 'HOSTED_PAGE',
            'startedAt' => '2025-01-01T00:00:00Z',
            'completedAt' => '2025-01-01T00:01:00Z',
            'result' => ['click' => ['ip' => '127.0.0.1']],
            'error' => null,
        ]);

        $this->assertSame('step_1', $step->stepId);
        $this->assertSame('CLICK', $step->type);
        $this->assertSame('COMPLETED', $step->status);
        $this->assertSame(1, $step->order);
        $this->assertSame('HOSTED_PAGE', $step->captureMode);
        $this->assertInstanceOf(StepResult::class, $step->result);
        $this->assertNull($step->error);
    }

    public function testStepFromArrayMinimal(): void
    {
        $step = Step::fromArray([
            'tenantId' => 't1',
            'transactionId' => 'tx_1',
            'stepId' => 's1',
            'type' => 'OTP',
            'status' => 'PENDING',
            'order' => 0,
            'attempts' => 0,
            'maxAttempts' => 3,
        ]);

        $this->assertNull($step->captureMode);
        $this->assertNull($step->startedAt);
        $this->assertNull($step->completedAt);
        $this->assertNull($step->result);
        $this->assertNull($step->error);
    }

    public function testStepToArrayOmitsNulls(): void
    {
        $step = new Step(
            tenantId: 't1',
            transactionId: 'tx_1',
            stepId: 's1',
            type: 'CLICK',
            status: 'PENDING',
            order: 0,
            attempts: 0,
            maxAttempts: 3,
        );
        $arr = $step->toArray();

        $this->assertArrayNotHasKey('captureMode', $arr);
        $this->assertArrayNotHasKey('startedAt', $arr);
        $this->assertArrayNotHasKey('completedAt', $arr);
        $this->assertArrayNotHasKey('result', $arr);
        $this->assertArrayNotHasKey('error', $arr);
    }

    public function testStepWithError(): void
    {
        $step = Step::fromArray([
            'tenantId' => 't1',
            'transactionId' => 'tx_1',
            'stepId' => 's1',
            'type' => 'LIVENESS',
            'status' => 'FAILED',
            'order' => 0,
            'attempts' => 3,
            'maxAttempts' => 3,
            'error' => 'Max attempts exceeded',
        ]);

        $this->assertSame('Max attempts exceeded', $step->error);
        $this->assertArrayHasKey('error', $step->toArray());
    }

    // ── Transaction ─────────────────────────────────────────────────

    public function testTransactionFromArrayFull(): void
    {
        $tx = Transaction::fromArray([
            'tenantId' => 'tenant_1',
            'transactionId' => 'tx_abc',
            'status' => 'active',
            'purpose' => 'DOCUMENT_SIGNATURE',
            'policy' => ['profile' => 'BIOMETRIC'],
            'signer' => ['name' => 'Joao', 'userExternalId' => 'ext_1'],
            'steps' => [
                ['tenantId' => 'tenant_1', 'transactionId' => 'tx_abc', 'stepId' => 's1', 'type' => 'CLICK', 'status' => 'PENDING', 'order' => 0, 'attempts' => 0, 'maxAttempts' => 3],
            ],
            'expiresAt' => '2025-12-31T23:59:59Z',
            'createdAt' => '2025-01-01T00:00:00Z',
            'updatedAt' => '2025-01-01T00:00:00Z',
            'documentGroupId' => 'dg_1',
            'signerIndex' => 0,
            'totalSigners' => 2,
            'metadata' => ['ref' => 'contract-123'],
        ]);

        $this->assertSame('tx_abc', $tx->transactionId);
        $this->assertSame('active', $tx->status);
        $this->assertSame('DOCUMENT_SIGNATURE', $tx->purpose);
        $this->assertInstanceOf(Policy::class, $tx->policy);
        $this->assertSame('BIOMETRIC', $tx->policy->profile);
        $this->assertInstanceOf(Signer::class, $tx->signer);
        $this->assertCount(1, $tx->steps);
        $this->assertInstanceOf(Step::class, $tx->steps[0]);
        $this->assertSame('dg_1', $tx->documentGroupId);
        $this->assertSame(0, $tx->signerIndex);
        $this->assertSame(2, $tx->totalSigners);
        $this->assertSame(['ref' => 'contract-123'], $tx->metadata);
    }

    public function testTransactionFromArrayMinimal(): void
    {
        $tx = Transaction::fromArray([
            'tenantId' => 't1',
            'transactionId' => 'tx_1',
            'status' => 'draft',
            'purpose' => 'ACTION_AUTHENTICATION',
            'policy' => ['profile' => 'CLICK_ONLY'],
            'signer' => ['name' => 'Maria', 'userExternalId' => 'ext_2'],
            'steps' => [],
            'expiresAt' => '2025-12-31T23:59:59Z',
            'createdAt' => '2025-01-01T00:00:00Z',
            'updatedAt' => '2025-01-01T00:00:00Z',
        ]);

        $this->assertNull($tx->documentGroupId);
        $this->assertNull($tx->signerIndex);
        $this->assertNull($tx->totalSigners);
        $this->assertNull($tx->metadata);
    }

    public function testTransactionToArrayOmitsNulls(): void
    {
        $tx = Transaction::fromArray([
            'tenantId' => 't1',
            'transactionId' => 'tx_1',
            'status' => 'draft',
            'purpose' => 'DOCUMENT_SIGNATURE',
            'policy' => ['profile' => 'CLICK_ONLY'],
            'signer' => ['name' => 'Test', 'userExternalId' => 'ext_1'],
            'steps' => [],
            'expiresAt' => '2025-12-31T23:59:59Z',
            'createdAt' => '2025-01-01T00:00:00Z',
            'updatedAt' => '2025-01-01T00:00:00Z',
        ]);
        $arr = $tx->toArray();

        $this->assertArrayNotHasKey('documentGroupId', $arr);
        $this->assertArrayNotHasKey('signerIndex', $arr);
        $this->assertArrayNotHasKey('totalSigners', $arr);
        $this->assertArrayNotHasKey('metadata', $arr);
    }

    public function testTransactionFromEmptyArray(): void
    {
        $tx = Transaction::fromArray([]);

        $this->assertSame('', $tx->tenantId);
        $this->assertSame('', $tx->transactionId);
        $this->assertSame('', $tx->status);
        $this->assertSame([], $tx->steps);
    }

    // ── CreateTransactionRequest ────────────────────────────────────

    public function testCreateTransactionRequestMinimal(): void
    {
        $req = CreateTransactionRequest::fromArray([
            'purpose' => 'DOCUMENT_SIGNATURE',
            'policy' => ['profile' => 'CLICK_ONLY'],
            'signer' => ['name' => 'Test', 'userExternalId' => 'ext_1'],
        ]);

        $this->assertSame('DOCUMENT_SIGNATURE', $req->purpose);
        $this->assertInstanceOf(Policy::class, $req->policy);
        $this->assertInstanceOf(Signer::class, $req->signer);
        $this->assertNull($req->document);
        $this->assertNull($req->action);
        $this->assertNull($req->metadata);
        $this->assertNull($req->expiresInMinutes);
    }

    public function testCreateTransactionRequestFull(): void
    {
        $req = CreateTransactionRequest::fromArray([
            'purpose' => 'DOCUMENT_SIGNATURE',
            'policy' => ['profile' => 'CUSTOM', 'customSteps' => ['LIVENESS']],
            'signer' => ['name' => 'Joao', 'userExternalId' => 'ext_1', 'cpf' => '11122233344'],
            'document' => ['content' => 'base64data', 'filename' => 'contract.pdf'],
            'digitalSignature' => ['algorithm' => 'RSA'],
            'documentGroupId' => 'dg_1',
            'signerIndex' => 0,
            'totalSigners' => 3,
            'metadata' => ['key' => 'value'],
            'expiresInMinutes' => 60,
        ]);

        $this->assertSame(['content' => 'base64data', 'filename' => 'contract.pdf'], $req->document);
        $this->assertSame('dg_1', $req->documentGroupId);
        $this->assertSame(0, $req->signerIndex);
        $this->assertSame(3, $req->totalSigners);
        $this->assertSame(60, $req->expiresInMinutes);
    }

    public function testCreateTransactionRequestToArrayOmitsNulls(): void
    {
        $req = new CreateTransactionRequest(
            purpose: 'DOCUMENT_SIGNATURE',
            policy: new Policy(profile: 'CLICK_ONLY'),
            signer: new Signer(name: 'Test', userExternalId: 'ext_1'),
        );
        $arr = $req->toArray();

        $this->assertSame('DOCUMENT_SIGNATURE', $arr['purpose']);
        $this->assertArrayNotHasKey('document', $arr);
        $this->assertArrayNotHasKey('action', $arr);
        $this->assertArrayNotHasKey('metadata', $arr);
        $this->assertArrayNotHasKey('expiresInMinutes', $arr);
    }

    // ── UploadDocumentRequest ───────────────────────────────────────

    public function testUploadDocumentRequestMinimal(): void
    {
        $req = UploadDocumentRequest::fromArray(['content' => 'base64data']);

        $this->assertSame('base64data', $req->content);
        $this->assertNull($req->filename);
    }

    public function testUploadDocumentRequestWithFilename(): void
    {
        $req = UploadDocumentRequest::fromArray([
            'content' => 'base64data',
            'filename' => 'doc.pdf',
        ]);

        $this->assertSame('doc.pdf', $req->filename);
        $arr = $req->toArray();
        $this->assertSame('doc.pdf', $arr['filename']);
    }

    public function testUploadDocumentRequestToArrayOmitsNullFilename(): void
    {
        $req = new UploadDocumentRequest(content: 'data');
        $arr = $req->toArray();
        $this->assertArrayNotHasKey('filename', $arr);
    }

    // ── ConfirmDocumentRequest ──────────────────────────────────────

    public function testConfirmDocumentRequestMinimal(): void
    {
        $req = ConfirmDocumentRequest::fromArray(['uploadToken' => 'tok_abc123']);

        $this->assertSame('tok_abc123', $req->uploadToken);
    }

    public function testConfirmDocumentRequestFull(): void
    {
        $req = ConfirmDocumentRequest::fromArray([
            'uploadToken' => 'tok_full_456',
        ]);

        $this->assertSame('tok_full_456', $req->uploadToken);
    }

    public function testConfirmDocumentRequestRoundtrip(): void
    {
        $data = ['uploadToken' => 'tok_roundtrip'];
        $req = ConfirmDocumentRequest::fromArray($data);
        $this->assertSame($data, $req->toArray());
    }

    // ── PresignResponse ─────────────────────────────────────────────

    public function testPresignResponseFromArray(): void
    {
        $resp = PresignResponse::fromArray([
            'uploadUrl' => 'https://s3.example.com/upload',
            'uploadToken' => 'tok_abc',
            's3Key' => 'uploads/doc.pdf',
            'expiresIn' => 3600,
            'contentType' => 'application/pdf',
            'instructions' => 'PUT the file to uploadUrl',
        ]);

        $this->assertSame('https://s3.example.com/upload', $resp->uploadUrl);
        $this->assertSame('tok_abc', $resp->uploadToken);
        $this->assertSame('uploads/doc.pdf', $resp->s3Key);
        $this->assertSame(3600, $resp->expiresIn);
        $this->assertSame('application/pdf', $resp->contentType);
        $this->assertSame('PUT the file to uploadUrl', $resp->instructions);
    }

    public function testPresignResponseRoundtrip(): void
    {
        $data = [
            'uploadUrl' => 'https://s3.example.com/upload',
            'uploadToken' => 'tok_roundtrip',
            's3Key' => 'uploads/test.pdf',
            'expiresIn' => 7200,
            'contentType' => 'application/pdf',
            'instructions' => 'Use PUT method',
        ];
        $resp = PresignResponse::fromArray($data);
        $this->assertSame($data, $resp->toArray());
    }

    // ── DownloadResponse ────────────────────────────────────────────

    public function testDownloadResponseFromArray(): void
    {
        $resp = DownloadResponse::fromArray([
            'transactionId' => 'tx_1',
            'documentHash' => 'sha256-abc',
            'originalUrl' => 'https://s3.example.com/original',
            'signedUrl' => 'https://s3.example.com/signed',
            'expiresIn' => 3600,
        ]);

        $this->assertSame('tx_1', $resp->transactionId);
        $this->assertSame('sha256-abc', $resp->documentHash);
        $this->assertSame('https://s3.example.com/original', $resp->originalUrl);
        $this->assertSame('https://s3.example.com/signed', $resp->signedUrl);
        $this->assertSame(3600, $resp->expiresIn);
    }

    public function testDownloadResponseRoundtrip(): void
    {
        $data = [
            'transactionId' => 'tx_2',
            'expiresIn' => 7200,
            'documentHash' => 'sha256-def',
            'originalUrl' => 'https://cdn.example.com/orig',
            'signedUrl' => 'https://cdn.example.com/signed',
        ];
        $resp = DownloadResponse::fromArray($data);
        $this->assertSame($data, $resp->toArray());
    }

    // ── PrepareSigningRequest ───────────────────────────────────────

    public function testPrepareSigningRequestFromArray(): void
    {
        $req = PrepareSigningRequest::fromArray([
            'certificateChainPems' => ['-----BEGIN CERT-----\nAAA\n-----END CERT-----'],
        ]);

        $this->assertCount(1, $req->certificateChainPems);
    }

    public function testPrepareSigningRequestRoundtrip(): void
    {
        $data = ['certificateChainPems' => ['cert1', 'cert2']];
        $req = PrepareSigningRequest::fromArray($data);
        $this->assertSame($data, $req->toArray());
    }

    // ── PrepareSigningResponse ──────────────────────────────────────

    public function testPrepareSigningResponseFromArray(): void
    {
        $resp = PrepareSigningResponse::fromArray([
            'signatureRequestId' => 'sr_123',
            'hashToSign' => 'deadbeef',
            'hashAlgorithm' => 'SHA-256',
            'signatureAlgorithm' => 'RSA-PKCS1v15',
        ]);

        $this->assertSame('sr_123', $resp->signatureRequestId);
        $this->assertSame('deadbeef', $resp->hashToSign);
        $this->assertSame('SHA-256', $resp->hashAlgorithm);
        $this->assertSame('RSA-PKCS1v15', $resp->signatureAlgorithm);
    }

    public function testPrepareSigningResponseRoundtrip(): void
    {
        $data = [
            'signatureRequestId' => 'sr_1',
            'hashToSign' => 'abc',
            'hashAlgorithm' => 'SHA-256',
            'signatureAlgorithm' => 'ECDSA',
        ];
        $resp = PrepareSigningResponse::fromArray($data);
        $this->assertSame($data, $resp->toArray());
    }

    // ── CompleteSigningRequest ──────────────────────────────────────

    public function testCompleteSigningRequestRoundtrip(): void
    {
        $data = [
            'signatureRequestId' => 'sr_1',
            'rawSignatureBase64' => 'c2lnbmF0dXJl',
        ];
        $req = CompleteSigningRequest::fromArray($data);
        $this->assertSame('sr_1', $req->signatureRequestId);
        $this->assertSame('c2lnbmF0dXJl', $req->rawSignatureBase64);
        $this->assertSame($data, $req->toArray());
    }

    // ── CompleteSigningResponse ─────────────────────────────────────

    public function testCompleteSigningResponseFromArray(): void
    {
        $resp = CompleteSigningResponse::fromArray([
            'stepId' => 'step_1',
            'status' => 'COMPLETED',
            'result' => ['digitalSignature' => ['algorithm' => 'RSA']],
        ]);

        $this->assertSame('step_1', $resp->stepId);
        $this->assertSame('COMPLETED', $resp->status);
        $this->assertSame(['digitalSignature' => ['algorithm' => 'RSA']], $resp->result);
    }

    public function testCompleteSigningResponseRoundtrip(): void
    {
        $data = [
            'stepId' => 's1',
            'status' => 'COMPLETED',
            'result' => ['digitalSignature' => ['valid' => true]],
        ];
        $resp = CompleteSigningResponse::fromArray($data);
        $this->assertSame($data, $resp->toArray());
    }

    // ── StartStepRequest ────────────────────────────────────────────

    public function testStartStepRequestEmpty(): void
    {
        $req = StartStepRequest::fromArray([]);
        $this->assertNull($req->captureMode);
    }

    public function testStartStepRequestWithCaptureMode(): void
    {
        $req = StartStepRequest::fromArray(['captureMode' => 'BANK_APP']);
        $this->assertSame('BANK_APP', $req->captureMode);
    }

    public function testStartStepRequestToArrayOmitsNulls(): void
    {
        $req = new StartStepRequest();
        $this->assertSame([], $req->toArray());
    }

    public function testStartStepRequestRoundtrip(): void
    {
        $data = ['captureMode' => 'HOSTED_PAGE'];
        $req = StartStepRequest::fromArray($data);
        $this->assertSame($data, $req->toArray());
    }

    // ── StartStepResponse ───────────────────────────────────────────

    public function testStartStepResponseMinimal(): void
    {
        $resp = StartStepResponse::fromArray([
            'stepId' => 's1',
            'type' => 'CLICK',
            'status' => 'IN_PROGRESS',
        ]);

        $this->assertSame('s1', $resp->stepId);
        $this->assertSame('CLICK', $resp->type);
        $this->assertSame('IN_PROGRESS', $resp->status);
        $this->assertNull($resp->livenessSessionId);
        $this->assertNull($resp->hostedUrl);
        $this->assertNull($resp->message);
        $this->assertNull($resp->otpCode);
    }

    public function testStartStepResponseWithLiveness(): void
    {
        $resp = StartStepResponse::fromArray([
            'stepId' => 's1',
            'type' => 'LIVENESS',
            'status' => 'IN_PROGRESS',
            'livenessSessionId' => 'liveness_abc',
        ]);

        $this->assertSame('liveness_abc', $resp->livenessSessionId);
    }

    public function testStartStepResponseWithOtp(): void
    {
        $resp = StartStepResponse::fromArray([
            'stepId' => 's1',
            'type' => 'OTP',
            'status' => 'IN_PROGRESS',
            'otpCode' => '123456',
            'message' => 'Code sent to phone',
        ]);

        $this->assertSame('123456', $resp->otpCode);
        $this->assertSame('Code sent to phone', $resp->message);
    }

    public function testStartStepResponseWithHostedUrl(): void
    {
        $resp = StartStepResponse::fromArray([
            'stepId' => 's1',
            'type' => 'BIOMETRIC_MATCH',
            'status' => 'IN_PROGRESS',
            'hostedUrl' => 'https://capture.signdocs.com.br/session/abc',
        ]);

        $this->assertSame('https://capture.signdocs.com.br/session/abc', $resp->hostedUrl);
    }

    // ── TransactionListParams ───────────────────────────────────────

    public function testTransactionListParamsEmpty(): void
    {
        $params = TransactionListParams::fromArray([]);
        $arr = $params->toArray();

        $this->assertEmpty($arr);
    }

    public function testTransactionListParamsFull(): void
    {
        $params = TransactionListParams::fromArray([
            'status' => 'active',
            'userExternalId' => 'ext_1',
            'documentGroupId' => 'dg_1',
            'limit' => 10,
            'nextToken' => 'token_abc',
        ]);

        $this->assertSame('active', $params->status);
        $this->assertSame('ext_1', $params->userExternalId);
        $this->assertSame(10, $params->limit);
        $this->assertSame('token_abc', $params->nextToken);
    }

    public function testTransactionListParamsToArrayOmitsNulls(): void
    {
        $params = new TransactionListParams(status: 'active');
        $arr = $params->toArray();

        $this->assertSame('active', $arr['status']);
        $this->assertArrayNotHasKey('userExternalId', $arr);
        $this->assertArrayNotHasKey('limit', $arr);
        $this->assertArrayNotHasKey('nextToken', $arr);
    }

    // ── TransactionListResponse ─────────────────────────────────────

    public function testTransactionListResponseFromArray(): void
    {
        $resp = TransactionListResponse::fromArray([
            'transactions' => [
                ['tenantId' => 't1', 'transactionId' => 'tx_1', 'status' => 'active', 'purpose' => 'DOCUMENT_SIGNATURE', 'policy' => ['profile' => 'CLICK_ONLY'], 'signer' => ['name' => 'A', 'userExternalId' => 'e1'], 'steps' => [], 'expiresAt' => '', 'createdAt' => '', 'updatedAt' => ''],
            ],
            'count' => 1,
            'nextToken' => 'next_abc',
        ]);

        $this->assertCount(1, $resp->transactions);
        $this->assertInstanceOf(Transaction::class, $resp->transactions[0]);
        $this->assertSame(1, $resp->count);
        $this->assertSame('next_abc', $resp->nextToken);
    }

    public function testTransactionListResponseNoNextToken(): void
    {
        $resp = TransactionListResponse::fromArray([
            'transactions' => [],
            'count' => 0,
        ]);

        $this->assertSame([], $resp->transactions);
        $this->assertSame(0, $resp->count);
        $this->assertNull($resp->nextToken);
    }

    // ── RegisterWebhookRequest ──────────────────────────────────────

    public function testRegisterWebhookRequestRoundtrip(): void
    {
        $data = [
            'url' => 'https://example.com/webhook',
            'events' => ['transaction.completed', 'transaction.cancelled'],
        ];
        $req = RegisterWebhookRequest::fromArray($data);

        $this->assertSame('https://example.com/webhook', $req->url);
        $this->assertSame(['transaction.completed', 'transaction.cancelled'], $req->events);
        $this->assertSame($data, $req->toArray());
    }

    // ── RegisterWebhookResponse ─────────────────────────────────────

    public function testRegisterWebhookResponseFromArray(): void
    {
        $resp = RegisterWebhookResponse::fromArray([
            'webhookId' => 'wh_1',
            'url' => 'https://example.com/hook',
            'secret' => 'whsec_abc123',
            'events' => ['transaction.completed'],
            'status' => 'ACTIVE',
            'createdAt' => '2025-01-01T00:00:00Z',
        ]);

        $this->assertSame('wh_1', $resp->webhookId);
        $this->assertSame('whsec_abc123', $resp->secret);
        $this->assertSame('ACTIVE', $resp->status);
    }

    public function testRegisterWebhookResponseRoundtrip(): void
    {
        $data = [
            'webhookId' => 'wh_1',
            'url' => 'https://example.com/hook',
            'secret' => 'whsec_xyz',
            'events' => ['transaction.completed'],
            'status' => 'ACTIVE',
            'createdAt' => '2025-06-01T00:00:00Z',
        ];
        $resp = RegisterWebhookResponse::fromArray($data);
        $this->assertSame($data, $resp->toArray());
    }

    // ── Webhook ─────────────────────────────────────────────────────

    public function testWebhookFromArray(): void
    {
        $wh = Webhook::fromArray([
            'webhookId' => 'wh_1',
            'url' => 'https://example.com/hook',
            'events' => ['transaction.completed'],
            'status' => 'ACTIVE',
            'createdAt' => '2025-01-01T00:00:00Z',
        ]);

        $this->assertSame('wh_1', $wh->webhookId);
        $this->assertSame('ACTIVE', $wh->status);
    }

    public function testWebhookRoundtrip(): void
    {
        $data = [
            'webhookId' => 'wh_2',
            'url' => 'https://test.com/wh',
            'events' => ['step.completed', 'transaction.cancelled'],
            'status' => 'ACTIVE',
            'createdAt' => '2025-03-15T12:00:00Z',
        ];
        $wh = Webhook::fromArray($data);
        $this->assertSame($data, $wh->toArray());
    }

    // ── WebhookTestResponse ─────────────────────────────────────────

    public function testWebhookTestResponseFromArray(): void
    {
        $resp = WebhookTestResponse::fromArray([
            'deliveryId' => 'del_1',
            'status' => 'delivered',
            'statusCode' => 200,
        ]);

        $this->assertSame('del_1', $resp->deliveryId);
        $this->assertSame('delivered', $resp->status);
        $this->assertSame(200, $resp->statusCode);
    }

    public function testWebhookTestResponseWithoutStatusCode(): void
    {
        $resp = WebhookTestResponse::fromArray([
            'deliveryId' => 'del_2',
            'status' => 'failed',
        ]);

        $this->assertNull($resp->statusCode);
    }

    // ── Evidence ────────────────────────────────────────────────────

    public function testEvidenceFromArrayFull(): void
    {
        $ev = Evidence::fromArray([
            'tenantId' => 't1',
            'transactionId' => 'tx_1',
            'evidenceId' => 'ev_1',
            'status' => 'COMPLETED',
            'signer' => ['name' => 'Joao', 'cpf' => '11122233344'],
            'steps' => [['type' => 'CLICK', 'status' => 'COMPLETED']],
            'createdAt' => '2025-01-01T00:00:00Z',
            'document' => ['hash' => 'abc123', 'filename' => 'doc.pdf'],
            'completedAt' => '2025-01-01T00:05:00Z',
        ]);

        $this->assertSame('ev_1', $ev->evidenceId);
        $this->assertSame('COMPLETED', $ev->status);
        $this->assertSame(['hash' => 'abc123', 'filename' => 'doc.pdf'], $ev->document);
        $this->assertSame('2025-01-01T00:05:00Z', $ev->completedAt);
    }

    public function testEvidenceFromArrayMinimal(): void
    {
        $ev = Evidence::fromArray([
            'tenantId' => 't1',
            'transactionId' => 'tx_1',
            'evidenceId' => 'ev_1',
            'status' => 'PENDING',
            'signer' => [],
            'steps' => [],
            'createdAt' => '2025-01-01T00:00:00Z',
        ]);

        $this->assertNull($ev->document);
        $this->assertNull($ev->completedAt);
    }

    public function testEvidenceToArrayOmitsNulls(): void
    {
        $ev = new Evidence(
            tenantId: 't1',
            transactionId: 'tx_1',
            evidenceId: 'ev_1',
            status: 'PENDING',
            signer: [],
            steps: [],
            createdAt: '2025-01-01T00:00:00Z',
        );
        $arr = $ev->toArray();

        $this->assertArrayNotHasKey('document', $arr);
        $this->assertArrayNotHasKey('completedAt', $arr);
    }

    // ── VerificationResponse ────────────────────────────────────────

    public function testVerificationResponseFromArray(): void
    {
        $resp = VerificationResponse::fromArray([
            'evidenceId' => 'ev_1',
            'transactionId' => 'tx_1',
            'status' => 'COMPLETED',
            'purpose' => 'DOCUMENT_SIGNATURE',
            'documentHash' => 'sha256-doc',
            'evidenceHash' => 'sha256-ev',
            'policy' => ['profile' => 'CLICK_ONLY'],
            'signer' => ['displayName' => 'Joao'],
            'steps' => [['type' => 'CLICK', 'status' => 'COMPLETED']],
            'tenantName' => 'Acme Corp',
            'createdAt' => '2025-01-01T00:00:00Z',
            'completedAt' => '2025-01-01T00:05:00Z',
        ]);

        $this->assertSame('DOCUMENT_SIGNATURE', $resp->purpose);
        $this->assertSame('ev_1', $resp->evidenceId);
        $this->assertSame('2025-01-01T00:05:00Z', $resp->completedAt);
        $this->assertSame('sha256-doc', $resp->documentHash);
        $this->assertSame(['displayName' => 'Joao'], $resp->signer);
    }

    public function testVerificationResponseWithoutCompletedAt(): void
    {
        $resp = VerificationResponse::fromArray([
            'evidenceId' => 'ev_2',
            'transactionId' => 'tx_2',
            'status' => 'PENDING',
            'purpose' => 'DOCUMENT_SIGNATURE',
            'signer' => [],
        ]);

        $this->assertSame('PENDING', $resp->status);
        $this->assertNull($resp->completedAt);
    }

    // ── VerificationDownloadsResponse ───────────────────────────────

    public function testVerificationDownloadsResponseFull(): void
    {
        $resp = VerificationDownloadsResponse::fromArray([
            'evidenceId' => 'ev_1',
            'downloads' => [
                'evidencePack' => ['url' => 'https://cdn.example.com/pack.p7m', 'expiresIn' => 3600],
                'signedPdf' => ['url' => 'https://cdn.example.com/signed.pdf', 'expiresIn' => 3600],
                'finalPdf' => ['url' => 'https://cdn.example.com/final.pdf', 'expiresIn' => 3600],
            ],
        ]);

        $this->assertSame('ev_1', $resp->evidenceId);
        $this->assertSame('https://cdn.example.com/pack.p7m', $resp->downloads['evidencePack']['url']);
        $this->assertSame('https://cdn.example.com/signed.pdf', $resp->downloads['signedPdf']['url']);
    }

    public function testVerificationDownloadsResponseMinimal(): void
    {
        $resp = VerificationDownloadsResponse::fromArray([
            'evidenceId' => 'ev_2',
            'downloads' => [],
        ]);

        $this->assertSame('ev_2', $resp->evidenceId);
        $this->assertSame([], $resp->downloads);
    }

    // ── EnrollUserRequest ───────────────────────────────────────────

    public function testEnrollUserRequestMinimal(): void
    {
        $req = EnrollUserRequest::fromArray([
            'image' => 'base64imagedata',
            'cpf' => '12345678901',
        ]);

        $this->assertSame('base64imagedata', $req->image);
        $this->assertSame('12345678901', $req->cpf);
        $this->assertSame('BANK_PROVIDED', $req->source);
    }

    public function testEnrollUserRequestWithSource(): void
    {
        $req = EnrollUserRequest::fromArray([
            'image' => 'base64img',
            'cpf' => '98765432100',
            'source' => 'FIRST_LIVENESS',
        ]);

        $this->assertSame('FIRST_LIVENESS', $req->source);
    }

    public function testEnrollUserRequestRoundtrip(): void
    {
        $data = [
            'image' => 'base64photo',
            'cpf' => '11122233344',
            'source' => 'DOCUMENT_PHOTO',
        ];
        $req = EnrollUserRequest::fromArray($data);
        $this->assertSame($data, $req->toArray());
    }

    // ── EnrollUserResponse ──────────────────────────────────────────

    public function testEnrollUserResponseFromArray(): void
    {
        $resp = EnrollUserResponse::fromArray([
            'userExternalId' => 'ext_1',
            'enrollmentHash' => 'sha256-enroll-hash',
            'enrollmentVersion' => 1,
            'enrollmentSource' => 'BANK_PROVIDED',
            'enrolledAt' => '2025-01-01T00:00:00Z',
            'cpf' => '12345678901',
            'faceConfidence' => 0.98,
        ]);

        $this->assertSame('ext_1', $resp->userExternalId);
        $this->assertSame('sha256-enroll-hash', $resp->enrollmentHash);
        $this->assertSame(1, $resp->enrollmentVersion);
        $this->assertSame('BANK_PROVIDED', $resp->enrollmentSource);
        $this->assertSame('2025-01-01T00:00:00Z', $resp->enrolledAt);
        $this->assertSame('12345678901', $resp->cpf);
        $this->assertSame(0.98, $resp->faceConfidence);
    }

    public function testEnrollUserResponseRoundtrip(): void
    {
        $data = [
            'userExternalId' => 'ext_2',
            'enrollmentHash' => 'sha256-hash-2',
            'enrollmentVersion' => 2,
            'enrollmentSource' => 'FIRST_LIVENESS',
            'enrolledAt' => '2025-06-01T12:00:00Z',
            'cpf' => '98765432100',
            'faceConfidence' => 0.95,
        ];
        $resp = EnrollUserResponse::fromArray($data);
        $this->assertSame($data, $resp->toArray());
    }

    // ── HealthCheckResponse ─────────────────────────────────────────

    public function testHealthCheckResponseMinimal(): void
    {
        $resp = HealthCheckResponse::fromArray([
            'status' => 'healthy',
            'version' => '1.0.0',
            'timestamp' => '2025-01-01T00:00:00Z',
        ]);

        $this->assertSame('healthy', $resp->status);
        $this->assertSame('1.0.0', $resp->version);
        $this->assertNull($resp->services);
    }

    public function testHealthCheckResponseWithServices(): void
    {
        $resp = HealthCheckResponse::fromArray([
            'status' => 'degraded',
            'version' => '1.0.0',
            'timestamp' => '2025-01-01T00:00:00Z',
            'services' => [
                'database' => ['status' => 'healthy'],
                'storage' => ['status' => 'degraded'],
            ],
        ]);

        $this->assertSame('degraded', $resp->status);
        $this->assertCount(2, $resp->services);
        $this->assertSame('healthy', $resp->services['database']['status']);
    }

    // ── HealthHistoryResponse ───────────────────────────────────────

    public function testHealthHistoryResponseFromArray(): void
    {
        $resp = HealthHistoryResponse::fromArray([
            'entries' => [
                ['status' => 'healthy', 'version' => '1.0.0', 'timestamp' => '2025-01-01T00:00:00Z'],
                ['status' => 'healthy', 'version' => '1.0.0', 'timestamp' => '2025-01-01T01:00:00Z'],
            ],
        ]);

        $this->assertCount(2, $resp->entries);
        $this->assertInstanceOf(HealthCheckResponse::class, $resp->entries[0]);
    }

    public function testHealthHistoryResponseEmpty(): void
    {
        $resp = HealthHistoryResponse::fromArray(['entries' => []]);
        $this->assertSame([], $resp->entries);
    }

    // ── CombinedStampResponse ───────────────────────────────────────

    public function testCombinedStampResponseFromArray(): void
    {
        $resp = CombinedStampResponse::fromArray([
            'groupId' => 'dg_1',
            'signerCount' => 3,
            'downloadUrl' => 'https://cdn.example.com/stamped.pdf',
            'expiresIn' => 3600,
        ]);

        $this->assertSame('dg_1', $resp->groupId);
        $this->assertSame(3, $resp->signerCount);
        $this->assertSame('https://cdn.example.com/stamped.pdf', $resp->downloadUrl);
        $this->assertSame(3600, $resp->expiresIn);
    }

    public function testCombinedStampResponseRoundtrip(): void
    {
        $data = [
            'groupId' => 'dg_2',
            'signerCount' => 2,
            'downloadUrl' => 'https://cdn.example.com/stamp2.pdf',
            'expiresIn' => 7200,
        ];
        $resp = CombinedStampResponse::fromArray($data);
        $this->assertSame($data, $resp->toArray());
    }

    // ── Fixture-based deserialization tests ─────────────────────────

    public function testTransactionFromFixtureCreate(): void
    {
        $fixture = $this->loadFixture('transactions-create.json');
        $body = $fixture['response']['body'];
        $tx = Transaction::fromArray($body);

        $this->assertSame('abc123', $tx->tenantId);
        $this->assertSame('tx-uuid-001', $tx->transactionId);
        $this->assertSame('CREATED', $tx->status);
        $this->assertSame('DOCUMENT_SIGNATURE', $tx->purpose);
        $this->assertInstanceOf(Policy::class, $tx->policy);
        $this->assertSame('CLICK_ONLY', $tx->policy->profile);
        $this->assertInstanceOf(Signer::class, $tx->signer);
        $this->assertSame('João Silva', $tx->signer->name);
        $this->assertSame('joao@example.com', $tx->signer->email);
        $this->assertSame('12345678901', $tx->signer->cpf);
        $this->assertCount(1, $tx->steps);
        $this->assertSame('step-uuid-001', $tx->steps[0]->stepId);
        $this->assertSame('CLICK_ACCEPT', $tx->steps[0]->type);
        $this->assertSame('PENDING', $tx->steps[0]->status);
        $this->assertSame(['contractId' => 'CTR-2024-001'], $tx->metadata);
        $this->assertSame('2024-11-16T00:00:00.000Z', $tx->expiresAt);
    }

    public function testTransactionFromFixtureGetWithNestedSteps(): void
    {
        $fixture = $this->loadFixture('transactions-get.json');
        $body = $fixture['response']['body'];
        $tx = Transaction::fromArray($body);

        $this->assertSame('tx-uuid-001', $tx->transactionId);
        $this->assertSame('IN_PROGRESS', $tx->status);
        $this->assertSame('CLICK_PLUS_OTP', $tx->policy->profile);
        $this->assertCount(2, $tx->steps);

        $step1 = $tx->steps[0];
        $this->assertSame('step-uuid-001', $step1->stepId);
        $this->assertSame('CLICK_ACCEPT', $step1->type);
        $this->assertSame('COMPLETED', $step1->status);
        $this->assertSame(1, $step1->order);
        $this->assertSame(1, $step1->attempts);
        $this->assertSame('2024-11-15T00:01:00.000Z', $step1->completedAt);
        $this->assertNotNull($step1->result);
        $this->assertSame(['accepted' => true, 'textVersion' => 'v1.0'], $step1->result->click);

        $step2 = $tx->steps[1];
        $this->assertSame('step-uuid-002', $step2->stepId);
        $this->assertSame('OTP_CHALLENGE', $step2->type);
        $this->assertSame('PENDING', $step2->status);
        $this->assertSame(0, $step2->attempts);
        $this->assertNull($step2->completedAt);
    }

    public function testTransactionListResponseFromFixture(): void
    {
        $fixture = $this->loadFixture('transactions-list.json');
        $body = $fixture['response']['body'];
        $resp = TransactionListResponse::fromArray($body);

        $this->assertSame(2, $resp->count);
        $this->assertCount(2, $resp->transactions);
        $this->assertSame(
            'eyJQSyI6IlRFTkFOVCNhYmMxMjMiLCJTSyI6IlRYI3R4LXV1aWQtMDAzIn0=',
            $resp->nextToken,
        );

        $first = $resp->transactions[0];
        $this->assertInstanceOf(Transaction::class, $first);
        $this->assertSame('tx-uuid-002', $first->transactionId);
        $this->assertSame('COMPLETED', $first->status);
        $this->assertSame('Maria Santos', $first->signer->name);

        $second = $resp->transactions[1];
        $this->assertSame('tx-uuid-003', $second->transactionId);
        $this->assertSame('BIOMETRIC', $second->policy->profile);
        $this->assertSame('Pedro Costa', $second->signer->name);
    }

    public function testEvidenceFromFixture(): void
    {
        $fixture = $this->loadFixture('evidence-get.json');
        $body = $fixture['response']['body'];
        $ev = Evidence::fromArray($body);

        $this->assertSame('abc123', $ev->tenantId);
        $this->assertSame('tx-uuid-001', $ev->transactionId);
        $this->assertSame('ev-uuid-001', $ev->evidenceId);
        $this->assertSame('COMPLETED', $ev->status);
        $this->assertSame('João Silva', $ev->signer['name']);
        $this->assertSame('12345678901', $ev->signer['cpf']);
        $this->assertCount(1, $ev->steps);
        $this->assertSame('CLICK_ACCEPT', $ev->steps[0]['type']);
        $this->assertNotNull($ev->document);
        $this->assertSame('contract.pdf', $ev->document['filename']);
        $this->assertSame('2024-11-15T00:01:00.000Z', $ev->completedAt);
    }

    public function testProblemDetailFromFixtureError400(): void
    {
        $fixture = $this->loadFixture('error-400.json');
        $body = $fixture['response']['body'];
        $pd = ProblemDetail::fromArray($body);

        $this->assertSame('https://api.signdocs.com.br/errors/bad-request', $pd->type);
        $this->assertSame('Bad Request', $pd->title);
        $this->assertSame(400, $pd->status);
        $this->assertSame('Invalid policy profile: UNKNOWN_PROFILE', $pd->detail);
        $this->assertSame('/v1/transactions', $pd->instance);
    }

    public function testRegisterWebhookResponseFromFixture(): void
    {
        $fixture = $this->loadFixture('webhooks-register.json');
        $body = $fixture['response']['body'];
        $resp = RegisterWebhookResponse::fromArray($body);

        $this->assertSame('wh-uuid-001', $resp->webhookId);
        $this->assertSame('https://example.com/webhooks/signdocs', $resp->url);
        $this->assertSame('whsec_generated_secret_abc123', $resp->secret);
        $this->assertSame(['TRANSACTION.COMPLETED', 'TRANSACTION.FAILED'], $resp->events);
        $this->assertSame('ACTIVE', $resp->status);
        $this->assertSame('2024-11-15T00:00:00.000Z', $resp->createdAt);
    }
}
