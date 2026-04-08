<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Tests;

use PHPUnit\Framework\TestCase;
use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\Evidence;
use SignDocsBrasil\Api\Resources\EvidenceResource;

final class EvidenceResourceTest extends TestCase
{
    private function mockHttp(): HttpClient
    {
        return $this->createMock(HttpClient::class);
    }

    public function testGetReturnsEvidence(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/transactions/tx_1/evidence')
            ->willReturn([
                'tenantId' => 'tenant_1',
                'transactionId' => 'tx_1',
                'evidenceId' => 'ev_abc',
                'status' => 'COMPLETED',
                'signer' => ['name' => 'Joao Silva', 'cpf' => '11122233344'],
                'steps' => [
                    ['type' => 'CLICK', 'status' => 'COMPLETED', 'result' => ['click' => ['ip' => '127.0.0.1']]],
                    ['type' => 'LIVENESS', 'status' => 'COMPLETED', 'result' => ['liveness' => ['score' => 0.98]]],
                ],
                'createdAt' => '2025-01-01T00:00:00Z',
                'document' => ['hash' => 'sha256abc', 'filename' => 'contract.pdf'],
                'completedAt' => '2025-01-01T00:05:00Z',
            ]);

        $evidence = new EvidenceResource($http);
        $result = $evidence->get('tx_1');

        $this->assertInstanceOf(Evidence::class, $result);
        $this->assertSame('ev_abc', $result->evidenceId);
        $this->assertSame('COMPLETED', $result->status);
        $this->assertSame('Joao Silva', $result->signer['name']);
        $this->assertCount(2, $result->steps);
        $this->assertSame('sha256abc', $result->document['hash']);
        $this->assertSame('2025-01-01T00:05:00Z', $result->completedAt);
    }

    public function testGetPendingEvidence(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/transactions/tx_2/evidence')
            ->willReturn([
                'tenantId' => 'tenant_1',
                'transactionId' => 'tx_2',
                'evidenceId' => 'ev_def',
                'status' => 'PENDING',
                'signer' => ['name' => 'Maria'],
                'steps' => [],
                'createdAt' => '2025-06-01T12:00:00Z',
            ]);

        $evidence = new EvidenceResource($http);
        $result = $evidence->get('tx_2');

        $this->assertSame('PENDING', $result->status);
        $this->assertNull($result->document);
        $this->assertNull($result->completedAt);
        $this->assertSame([], $result->steps);
    }

    public function testGetUsesCorrectEndpoint(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/transactions/tx_with-dashes/evidence')
            ->willReturn([
                'tenantId' => 't1',
                'transactionId' => 'tx_with-dashes',
                'evidenceId' => 'ev_1',
                'status' => 'COMPLETED',
                'signer' => [],
                'steps' => [],
                'createdAt' => '2025-01-01T00:00:00Z',
            ]);

        $evidence = new EvidenceResource($http);
        $evidence->get('tx_with-dashes');
    }
}
