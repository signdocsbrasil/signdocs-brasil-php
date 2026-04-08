<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Resources;

use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\Evidence;

final class EvidenceResource
{
    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    /**
     * Get the evidence package for a completed transaction.
     *
     * GET /v1/transactions/{transactionId}/evidence
     */
    public function get(string $transactionId, ?int $timeout = null): Evidence
    {
        $data = $this->http->request(
            'GET',
            "/v1/transactions/{$transactionId}/evidence",
            timeout: $timeout,
        );

        return Evidence::fromArray($data ?? []);
    }
}
