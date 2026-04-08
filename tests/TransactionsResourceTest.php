<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Tests;

use PHPUnit\Framework\TestCase;
use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\CreateTransactionRequest;
use SignDocsBrasil\Api\Models\Policy;
use SignDocsBrasil\Api\Models\Signer;
use SignDocsBrasil\Api\Models\TransactionListParams;

final class TransactionsResourceTest extends TestCase
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

    public function testCreateUsesIdempotency(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('requestWithIdempotency')
            ->with('POST', '/v1/transactions', $this->anything(), null)
            ->willReturn($this->mockTransaction());

        $tx = new \SignDocsBrasil\Api\Resources\TransactionsResource($http);
        $request = new CreateTransactionRequest(
            purpose: 'electronic_signature',
            policy: new Policy(profile: 'CLICK_ONLY'),
            signer: new Signer(name: 'Test', userExternalId: 'ext_1'),
        );
        $tx->create($request);
    }

    public function testCreateWithExplicitKey(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('requestWithIdempotency')
            ->with('POST', '/v1/transactions', $this->anything(), 'my-key')
            ->willReturn($this->mockTransaction());

        $tx = new \SignDocsBrasil\Api\Resources\TransactionsResource($http);
        $request = new CreateTransactionRequest(
            purpose: 'electronic_signature',
            policy: new Policy(profile: 'CLICK_ONLY'),
            signer: new Signer(name: 'Test', userExternalId: 'ext_1'),
        );
        $tx->create($request, 'my-key');
    }

    public function testList(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/transactions', null, $this->anything())
            ->willReturn([
                'transactions' => [$this->mockTransaction()],
                'count' => 1,
            ]);

        $tx = new \SignDocsBrasil\Api\Resources\TransactionsResource($http);
        $tx->list();
    }

    public function testGet(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/transactions/tx_1')
            ->willReturn($this->mockTransaction());

        $tx = new \SignDocsBrasil\Api\Resources\TransactionsResource($http);
        $tx->get('tx_1');
    }

    public function testCancelDeleteWithBody(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('DELETE', '/v1/transactions/tx_1')
            ->willReturn($this->mockTransaction(['status' => 'CANCELLED']));

        $tx = new \SignDocsBrasil\Api\Resources\TransactionsResource($http);
        $tx->cancel('tx_1');
    }

    public function testFinalize(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('POST', '/v1/transactions/tx_1/finalize')
            ->willReturn($this->mockTransaction(['status' => 'FINALIZED']));

        $tx = new \SignDocsBrasil\Api\Resources\TransactionsResource($http);
        $tx->finalize('tx_1');
    }

    // ── Pagination edge cases ───────────────────────────────────────

    public function testListEmptyPage(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/transactions', null, $this->anything())
            ->willReturn([
                'transactions' => [],
                'count' => 0,
            ]);

        $tx = new \SignDocsBrasil\Api\Resources\TransactionsResource($http);
        $result = $tx->list();

        $this->assertSame([], $result->transactions);
        $this->assertSame(0, $result->count);
        $this->assertNull($result->nextToken);
    }

    public function testListSinglePageNoNextToken(): void
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

        $tx = new \SignDocsBrasil\Api\Resources\TransactionsResource($http);
        $result = $tx->list();

        $this->assertCount(2, $result->transactions);
        $this->assertSame(2, $result->count);
        $this->assertNull($result->nextToken);
        $this->assertSame('tx_a', $result->transactions[0]->transactionId);
        $this->assertSame('tx_b', $result->transactions[1]->transactionId);
    }

    public function testListSinglePageAutoPaginationStops(): void
    {
        // Simulate auto-pagination by checking that a single page without
        // nextToken means iteration should end (no further requests).
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->willReturn([
                'transactions' => [
                    $this->mockTransaction(['transactionId' => 'tx_only']),
                ],
                'count' => 1,
            ]);

        $tx = new \SignDocsBrasil\Api\Resources\TransactionsResource($http);
        $page = $tx->list();

        // Verify we got items
        $this->assertCount(1, $page->transactions);
        $this->assertSame('tx_only', $page->transactions[0]->transactionId);
        // Verify no nextToken (auto-pagination should stop here)
        $this->assertNull($page->nextToken);
    }

    public function testListWithNextTokenPassedAsParam(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/transactions', null, ['nextToken' => 'page2_token'])
            ->willReturn([
                'transactions' => [
                    $this->mockTransaction(['transactionId' => 'tx_page2']),
                ],
                'count' => 1,
            ]);

        $tx = new \SignDocsBrasil\Api\Resources\TransactionsResource($http);
        $result = $tx->list(new TransactionListParams(nextToken: 'page2_token'));

        $this->assertCount(1, $result->transactions);
        $this->assertSame('tx_page2', $result->transactions[0]->transactionId);
    }
}
