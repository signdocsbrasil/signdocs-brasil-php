<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Resources;

use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\VerificationDownloadsResponse;
use SignDocsBrasil\Api\Models\VerificationResponse;

final class VerificationResource
{
    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    /**
     * Verify an evidence package by its ID.
     *
     * GET /v1/verify/{evidenceId} (no authentication required)
     */
    public function verify(string $evidenceId, ?int $timeout = null): VerificationResponse
    {
        $data = $this->http->request(
            'GET',
            "/v1/verify/{$evidenceId}",
            noAuth: true,
            timeout: $timeout,
        );

        return VerificationResponse::fromArray($data ?? []);
    }

    /**
     * Get download URLs for an evidence package.
     *
     * GET /v1/verify/{evidenceId}/downloads (no authentication required)
     */
    public function downloads(string $evidenceId, ?int $timeout = null): VerificationDownloadsResponse
    {
        $data = $this->http->request(
            'GET',
            "/v1/verify/{$evidenceId}/downloads",
            noAuth: true,
            timeout: $timeout,
        );

        return VerificationDownloadsResponse::fromArray($data ?? []);
    }
}
