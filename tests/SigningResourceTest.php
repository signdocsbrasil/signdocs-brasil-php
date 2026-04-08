<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Tests;

use PHPUnit\Framework\TestCase;
use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\CompleteSigningRequest;
use SignDocsBrasil\Api\Models\CompleteSigningResponse;
use SignDocsBrasil\Api\Models\PrepareSigningRequest;
use SignDocsBrasil\Api\Models\PrepareSigningResponse;
use SignDocsBrasil\Api\Resources\SigningResource;

final class SigningResourceTest extends TestCase
{
    private function mockHttp(): HttpClient
    {
        return $this->createMock(HttpClient::class);
    }

    public function testPreparePostsWithCertificateChain(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/v1/transactions/tx_1/signing/prepare',
                ['certificateChainPems' => ['cert1', 'cert2']],
            )
            ->willReturn([
                'signatureRequestId' => 'sr_123',
                'hashToSign' => 'deadbeef',
                'hashAlgorithm' => 'SHA-256',
                'signatureAlgorithm' => 'RSA-PKCS1v15',
            ]);

        $signing = new SigningResource($http);
        $result = $signing->prepare(
            'tx_1',
            new PrepareSigningRequest(certificateChainPems: ['cert1', 'cert2']),
        );

        $this->assertInstanceOf(PrepareSigningResponse::class, $result);
        $this->assertSame('sr_123', $result->signatureRequestId);
        $this->assertSame('deadbeef', $result->hashToSign);
        $this->assertSame('SHA-256', $result->hashAlgorithm);
        $this->assertSame('RSA-PKCS1v15', $result->signatureAlgorithm);
    }

    public function testCompletePostsWithSignedHash(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/v1/transactions/tx_1/signing/complete',
                [
                    'signatureRequestId' => 'sr_123',
                    'rawSignatureBase64' => 'c2lnbmF0dXJl',
                ],
            )
            ->willReturn([
                'stepId' => 'step_sign',
                'status' => 'COMPLETED',
                'result' => ['digitalSignature' => ['algorithm' => 'RSA', 'valid' => true]],
            ]);

        $signing = new SigningResource($http);
        $result = $signing->complete(
            'tx_1',
            new CompleteSigningRequest(
                signatureRequestId: 'sr_123',
                rawSignatureBase64: 'c2lnbmF0dXJl',
            ),
        );

        $this->assertInstanceOf(CompleteSigningResponse::class, $result);
        $this->assertSame('step_sign', $result->stepId);
        $this->assertSame('COMPLETED', $result->status);
        $this->assertTrue($result->result['digitalSignature']['valid']);
    }

    public function testPrepareUsesCorrectEndpointPath(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/v1/transactions/tx_special-chars_123/signing/prepare',
                $this->anything(),
            )
            ->willReturn([
                'signatureRequestId' => 'sr_1',
                'hashToSign' => 'abc',
                'hashAlgorithm' => 'SHA-256',
                'signatureAlgorithm' => 'ECDSA',
            ]);

        $signing = new SigningResource($http);
        $signing->prepare(
            'tx_special-chars_123',
            new PrepareSigningRequest(certificateChainPems: ['cert']),
        );
    }

    public function testCompleteUsesCorrectEndpointPath(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/v1/transactions/tx_456/signing/complete',
                $this->anything(),
            )
            ->willReturn([
                'stepId' => 's1',
                'status' => 'COMPLETED',
                'result' => [],
            ]);

        $signing = new SigningResource($http);
        $signing->complete(
            'tx_456',
            new CompleteSigningRequest(
                signatureRequestId: 'sr_1',
                rawSignatureBase64: 'sig',
            ),
        );
    }
}
