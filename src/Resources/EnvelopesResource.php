<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Resources;

use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\AddEnvelopeSessionRequest;
use SignDocsBrasil\Api\Models\CancelEnvelopeResponse;
use SignDocsBrasil\Api\Models\CreateEnvelopeRequest;
use SignDocsBrasil\Api\Models\Envelope;
use SignDocsBrasil\Api\Models\EnvelopeCombinedStampResponse;
use SignDocsBrasil\Api\Models\EnvelopeDetail;
use SignDocsBrasil\Api\Models\EnvelopeSession;

final class EnvelopesResource
{
    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    /**
     * Create a new envelope for multi-signer document signing.
     *
     * POST /v1/envelopes
     *
     * An X-Idempotency-Key header is automatically set. Pass an explicit
     * key to enable safe retries with the same idempotency guarantee.
     */
    public function create(
        CreateEnvelopeRequest $request,
        ?string $idempotencyKey = null,
        ?int $timeout = null,
    ): Envelope {
        $data = $this->http->requestWithIdempotency(
            'POST',
            '/v1/envelopes',
            $request->toArray(),
            $idempotencyKey,
            timeout: $timeout,
        );

        return Envelope::fromArray($data ?? []);
    }

    /**
     * Get the details of an envelope including session summaries.
     *
     * GET /v1/envelopes/{envelopeId}
     */
    public function get(string $envelopeId, ?int $timeout = null): EnvelopeDetail
    {
        $data = $this->http->request(
            'GET',
            "/v1/envelopes/{$envelopeId}",
            timeout: $timeout,
        );

        return EnvelopeDetail::fromArray($data ?? []);
    }

    /**
     * Add a signing session to an envelope for a specific signer.
     *
     * POST /v1/envelopes/{envelopeId}/sessions
     */
    public function addSession(
        string $envelopeId,
        AddEnvelopeSessionRequest $request,
        ?int $timeout = null,
    ): EnvelopeSession {
        $data = $this->http->request(
            'POST',
            "/v1/envelopes/{$envelopeId}/sessions",
            body: $request->toArray(),
            timeout: $timeout,
        );

        return EnvelopeSession::fromArray($data ?? []);
    }

    /**
     * Cancel an entire envelope.
     *
     * Transitions every non-terminal session and its transaction to CANCELLED
     * and marks the envelope CANCELLED, killing the pending signing links.
     * Signatures already collected are preserved — their evidence stays valid
     * and independently verifiable — and reported as preservedSignedCount.
     *
     * Prefer this over cancelling each session individually: it is one call, it
     * records the cancellation as a single auditable terminal event, and it is
     * the only way to move the envelope's own status. Cancelling the member
     * sessions one by one leaves the envelope itself ACTIVE.
     *
     * Idempotent: re-cancelling returns 200 with cancelledCount 0 and
     * alreadyCancelled true.
     *
     * POST /v1/envelopes/{envelopeId}/cancel
     *
     * @param string|null $reason Free-text reason recorded in the audit trail.
     *                            Defaults server-side to 'envelope_cancelled'.
     */
    public function cancel(string $envelopeId, ?string $reason = null, ?int $timeout = null): CancelEnvelopeResponse
    {
        $data = $this->http->request(
            'POST',
            "/v1/envelopes/{$envelopeId}/cancel",
            $reason !== null ? ['reason' => $reason] : [],
            timeout: $timeout,
        );

        return CancelEnvelopeResponse::fromArray($data ?? []);
    }

    /**
     * Generate a combined stamped PDF with all completed signatures.
     *
     * POST /v1/envelopes/{envelopeId}/combined-stamp
     */
    public function combinedStamp(string $envelopeId, ?int $timeout = null): EnvelopeCombinedStampResponse
    {
        $data = $this->http->request(
            'POST',
            "/v1/envelopes/{$envelopeId}/combined-stamp",
            timeout: $timeout,
        );

        return EnvelopeCombinedStampResponse::fromArray($data ?? []);
    }
}
