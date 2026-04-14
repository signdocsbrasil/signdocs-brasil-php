<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Resources;

use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\EnvelopeVerificationResponse;
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

    /**
     * Verify a multi-signer envelope by its ID. Returns the envelope status,
     * signers list (each with an `evidenceId` for drill-down), and consolidated
     * download URLs. For non-PDF envelopes signed with digital certificates,
     * the consolidated `.p7s` containing every signer's `SignerInfo` is exposed
     * via `$response->downloads['consolidatedSignature']`.
     *
     * GET /v1/verify/envelope/{envelopeId} (no authentication required)
     */
    public function verifyEnvelope(string $envelopeId, ?int $timeout = null): EnvelopeVerificationResponse
    {
        $data = $this->http->request(
            'GET',
            "/v1/verify/envelope/{$envelopeId}",
            noAuth: true,
            timeout: $timeout,
        );

        return EnvelopeVerificationResponse::fromArray($data ?? []);
    }
}
