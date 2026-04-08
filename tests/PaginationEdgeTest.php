<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Tests;

use PHPUnit\Framework\TestCase;
use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\TransactionListParams;
use SignDocsBrasil\Api\Resources\TransactionsResource;

/**
 * Phase 6: Pagination edge case tests for PHP SDK.
 * Tests empty results, single page, multi-page, limit boundaries,
 * and nextToken propagation.
 */
final class PaginationEdgeTest extends TestCase
{
    private function mockHttp(): HttpClient
    {
        return $this->createMock(HttpClient::class);
    }

    private function mockTransaction(string $id = 'tx_1', string $status = 'COMPLETED'): array
    {
        return [
            'tenantId' => 'ten_1',
            'transactionId' => $id,
            'status' => $status,
            'purpose' => 'DOCUMENT_SIGNATURE',
            'policy' => ['profile' => 'CLICK_ONLY'],
            'signer' => ['name' => 'Test', 'userExternalId' => 'ext_1'],
            'steps' => [],
            'createdAt' => '2024-01-01T00:00:00Z',
            'updatedAt' => '2024-01-01T00:00:00Z',
        ];
    }

    // ── Standard list() ───────────────────────────────────────

    public function testEmptyFirstPage(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->willReturn(['transactions' => [], 'count' => 0]);

        $tx = new TransactionsResource($http);
        $resp = $tx->list();

        $this->assertSame([], $resp->transactions);
        $this->assertSame(0, $resp->count);
        $this->assertNull($resp->nextToken);
    }

    public function testSinglePageNoNextToken(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->willReturn([
                'transactions' => [
                    $this->mockTransaction('tx_1'),
                    $this->mockTransaction('tx_2'),
                ],
                'count' => 2,
            ]);

        $tx = new TransactionsResource($http);
        $resp = $tx->list();

        $this->assertCount(2, $resp->transactions);
        $this->assertSame(2, $resp->count);
        $this->assertNull($resp->nextToken);
        $this->assertSame('tx_1', $resp->transactions[0]->transactionId);
        $this->assertSame('tx_2', $resp->transactions[1]->transactionId);
    }

    public function testNextTokenForwarded(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/transactions', null, $this->callback(function ($query) {
                return isset($query['nextToken']) && $query['nextToken'] === 'page2';
            }))
            ->willReturn([
                'transactions' => [$this->mockTransaction('tx_3')],
                'count' => 1,
                'nextToken' => 'page3',
            ]);

        $tx = new TransactionsResource($http);
        $resp = $tx->list(new TransactionListParams(nextToken: 'page2'));

        $this->assertSame('page3', $resp->nextToken);
    }

    public function testLimitOneReturnsSingleItem(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->willReturn([
                'transactions' => [$this->mockTransaction('tx_1')],
                'count' => 1,
                'nextToken' => 'next',
            ]);

        $tx = new TransactionsResource($http);
        $resp = $tx->list(new TransactionListParams(limit: 1));

        $this->assertCount(1, $resp->transactions);
        $this->assertSame('next', $resp->nextToken);
    }

    public function testMaxLimit100(): void
    {
        $http = $this->mockHttp();
        $items = array_map(
            fn(int $i) => $this->mockTransaction("tx_{$i}"),
            range(0, 99),
        );
        $http->expects($this->once())
            ->method('request')
            ->willReturn([
                'transactions' => $items,
                'count' => 100,
                'nextToken' => 'more',
            ]);

        $tx = new TransactionsResource($http);
        $resp = $tx->list(new TransactionListParams(limit: 100));

        $this->assertCount(100, $resp->transactions);
        $this->assertSame('more', $resp->nextToken);
    }

    public function testNullNextTokenMeansEnd(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->willReturn([
                'transactions' => [$this->mockTransaction('tx_last')],
                'count' => 1,
                'nextToken' => null,
            ]);

        $tx = new TransactionsResource($http);
        $resp = $tx->list();

        $this->assertNull($resp->nextToken);
    }

    // ── Manual multi-page pagination ────────────────────────────

    public function testManualPaginationTwoPages(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                [
                    'transactions' => [
                        $this->mockTransaction('tx_1'),
                        $this->mockTransaction('tx_2'),
                    ],
                    'count' => 2,
                    'nextToken' => 'page2',
                ],
                [
                    'transactions' => [$this->mockTransaction('tx_3')],
                    'count' => 1,
                ],
            );

        $tx = new TransactionsResource($http);

        // Page 1
        $page1 = $tx->list();
        $this->assertCount(2, $page1->transactions);
        $this->assertSame('page2', $page1->nextToken);

        // Page 2
        $page2 = $tx->list(new TransactionListParams(nextToken: $page1->nextToken));
        $this->assertCount(1, $page2->transactions);
        $this->assertNull($page2->nextToken);
    }

    public function testManualPaginationThreePages(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->exactly(3))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                [
                    'transactions' => [$this->mockTransaction('tx_1')],
                    'count' => 1,
                    'nextToken' => 'p2',
                ],
                [
                    'transactions' => [$this->mockTransaction('tx_2')],
                    'count' => 1,
                    'nextToken' => 'p3',
                ],
                [
                    'transactions' => [$this->mockTransaction('tx_3')],
                    'count' => 1,
                ],
            );

        $tx = new TransactionsResource($http);
        $allItems = [];
        $nextToken = null;

        do {
            $params = $nextToken ? new TransactionListParams(nextToken: $nextToken) : null;
            $page = $tx->list($params);
            array_push($allItems, ...$page->transactions);
            $nextToken = $page->nextToken;
        } while ($nextToken !== null);

        $this->assertCount(3, $allItems);
    }

    public function testManualPaginationEmptySecondPageTerminates(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                [
                    'transactions' => [$this->mockTransaction('tx_1')],
                    'count' => 1,
                    'nextToken' => 'page2',
                ],
                [
                    'transactions' => [],
                    'count' => 0,
                ],
            );

        $tx = new TransactionsResource($http);

        $page1 = $tx->list();
        $this->assertCount(1, $page1->transactions);

        $page2 = $tx->list(new TransactionListParams(nextToken: $page1->nextToken));
        $this->assertEmpty($page2->transactions);
        $this->assertNull($page2->nextToken);
    }

    // ── Auto-paginate ───────────────────────────────────────────

    public function testAutoPaginateTwoPages(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                [
                    'transactions' => [
                        $this->mockTransaction('tx_1'),
                        $this->mockTransaction('tx_2'),
                    ],
                    'count' => 2,
                    'nextToken' => 'page2',
                ],
                [
                    'transactions' => [$this->mockTransaction('tx_3')],
                    'count' => 1,
                ],
            );

        $tx = new TransactionsResource($http);
        $all = iterator_to_array($tx->listAutoPaginate(), false);

        $this->assertCount(3, $all);
        $this->assertSame('tx_1', $all[0]->transactionId);
        $this->assertSame('tx_2', $all[1]->transactionId);
        $this->assertSame('tx_3', $all[2]->transactionId);
    }

    public function testAutoPaginateEmptyResult(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->willReturn(['transactions' => [], 'count' => 0]);

        $tx = new TransactionsResource($http);
        $all = iterator_to_array($tx->listAutoPaginate(), false);

        $this->assertEmpty($all);
    }

    public function testAutoPaginateStopsOnNullNextToken(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->willReturn([
                'transactions' => [$this->mockTransaction('tx_only')],
                'count' => 1,
            ]);

        $tx = new TransactionsResource($http);
        $all = iterator_to_array(
            $tx->listAutoPaginate(new TransactionListParams(status: 'COMPLETED')),
            false,
        );

        $this->assertCount(1, $all);
        $this->assertSame('tx_only', $all[0]->transactionId);
    }
}
