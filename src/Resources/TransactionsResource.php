<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Resources;

use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\CancelTransactionResponse;
use SignDocsBrasil\Api\Models\CreateTransactionRequest;
use SignDocsBrasil\Api\Models\FinalizeResponse;
use SignDocsBrasil\Api\Models\Transaction;
use SignDocsBrasil\Api\Models\TransactionListParams;
use SignDocsBrasil\Api\Models\TransactionListResponse;

final class TransactionsResource
{
    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    /**
     * Create a new signing transaction.
     *
     * POST /v1/transactions
     *
     * An X-Idempotency-Key header is automatically set. Pass an explicit
     * key to enable safe retries with the same idempotency guarantee.
     */
    public function create(
        CreateTransactionRequest $request,
        ?string $idempotencyKey = null,
        ?int $timeout = null,
    ): Transaction {
        $data = $this->http->requestWithIdempotency(
            'POST',
            '/v1/transactions',
            $request->toArray(),
            $idempotencyKey,
            timeout: $timeout,
        );

        return Transaction::fromArray($data ?? []);
    }

    /**
     * List transactions with optional filters and pagination.
     *
     * GET /v1/transactions
     */
    public function list(?TransactionListParams $params = null, ?int $timeout = null): TransactionListResponse
    {
        $query = $params?->toArray();

        $data = $this->http->request('GET', '/v1/transactions', query: $query, timeout: $timeout);

        return TransactionListResponse::fromArray($data ?? []);
    }

    /**
     * Get a single transaction by ID.
     *
     * GET /v1/transactions/{transactionId}
     */
    public function get(string $transactionId, ?int $timeout = null): Transaction
    {
        $data = $this->http->request(
            'GET',
            "/v1/transactions/{$transactionId}",
            timeout: $timeout,
        );

        return Transaction::fromArray($data ?? []);
    }

    /**
     * Cancel a transaction.
     *
     * DELETE /v1/transactions/{transactionId}
     *
     * Note: Returns 200 with JSON body (not 204).
     */
    public function cancel(string $transactionId, ?int $timeout = null): CancelTransactionResponse
    {
        $data = $this->http->request(
            'DELETE',
            "/v1/transactions/{$transactionId}",
            timeout: $timeout,
        );

        return CancelTransactionResponse::fromArray($data ?? []);
    }

    /**
     * Finalize a transaction (mark all steps as complete).
     *
     * POST /v1/transactions/{transactionId}/finalize
     */
    public function finalize(string $transactionId, ?int $timeout = null): FinalizeResponse
    {
        $data = $this->http->request(
            'POST',
            "/v1/transactions/{$transactionId}/finalize",
            timeout: $timeout,
        );

        return FinalizeResponse::fromArray($data ?? []);
    }

    /**
     * Iterate through all transactions, automatically fetching subsequent pages.
     *
     * The caller's nextToken is ignored; pagination is managed internally.
     *
     * @param TransactionListParams|null $params Optional filter parameters
     * @return \Generator<int, Transaction>
     */
    public function listAutoPaginate(?TransactionListParams $params = null): \Generator
    {
        $pageParams = $params !== null
            ? new TransactionListParams(
                status: $params->status,
                userExternalId: $params->userExternalId,
                documentGroupId: $params->documentGroupId,
                limit: $params->limit,
                startDate: $params->startDate,
                endDate: $params->endDate,
            )
            : null;

        do {
            $response = $this->list($pageParams);

            foreach ($response->transactions as $tx) {
                yield $tx;
            }

            if ($response->nextToken === null) {
                break;
            }

            // Ensure we have a params object to carry the nextToken
            $pageParams ??= new TransactionListParams();
            $pageParams = new TransactionListParams(
                status: $pageParams->status,
                userExternalId: $pageParams->userExternalId,
                documentGroupId: $pageParams->documentGroupId,
                limit: $pageParams->limit,
                nextToken: $response->nextToken,
                startDate: $pageParams->startDate,
                endDate: $pageParams->endDate,
            );
        } while (true);
    }
}
