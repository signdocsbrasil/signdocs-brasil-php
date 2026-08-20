<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Resources;

use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\AdvanceSessionRequest;
use SignDocsBrasil\Api\Models\AdvanceSessionResponse;
use SignDocsBrasil\Api\Models\CancelSigningSessionResponse;
use SignDocsBrasil\Api\Models\CreateSigningSessionRequest;
use SignDocsBrasil\Api\Models\MintSigningLinkResponse;
use SignDocsBrasil\Api\Models\ResendOtpRequest;
use SignDocsBrasil\Api\Models\SigningSession;
use SignDocsBrasil\Api\Models\SigningSessionBootstrap;
use SignDocsBrasil\Api\Models\SigningSessionListParams;
use SignDocsBrasil\Api\Models\SigningSessionListResponse;
use SignDocsBrasil\Api\Models\SigningSessionStatus;

final class SigningSessionsResource
{
    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    /**
     * Create a new signing session.
     *
     * POST /v1/signing-sessions
     *
     * An X-Idempotency-Key header is automatically set. Pass an explicit
     * key to enable safe retries with the same idempotency guarantee.
     */
    public function create(
        CreateSigningSessionRequest $request,
        ?string $idempotencyKey = null,
        ?int $timeout = null,
    ): SigningSession {
        $data = $this->http->requestWithIdempotency(
            'POST',
            '/v1/signing-sessions',
            $request->toArray(),
            $idempotencyKey,
            timeout: $timeout,
        );

        return SigningSession::fromArray($data ?? []);
    }

    /**
     * Get the status of a signing session.
     *
     * GET /v1/signing-sessions/{sessionId}/status
     */
    public function getStatus(string $sessionId, ?int $timeout = null): SigningSessionStatus
    {
        $data = $this->http->request(
            'GET',
            "/v1/signing-sessions/{$sessionId}/status",
            timeout: $timeout,
        );

        return SigningSessionStatus::fromArray($data ?? []);
    }

    /**
     * Cancel a signing session.
     *
     * POST /v1/signing-sessions/{sessionId}/cancel
     */
    public function cancel(string $sessionId, ?int $timeout = null): CancelSigningSessionResponse
    {
        $data = $this->http->request(
            'POST',
            "/v1/signing-sessions/{$sessionId}/cancel",
            timeout: $timeout,
        );

        return CancelSigningSessionResponse::fromArray($data ?? []);
    }

    /**
     * Mint a fresh signing URL for an existing session and return it, instead
     * of e-mailing it.
     *
     * POST /v1/signing-sessions/{sessionId}/link
     *
     * A signing link is single-use: once the signer finishes — or the embed
     * token is otherwise consumed — reopening the same URL returns
     * `401 Embed token has been consumed`. This issues a new one without
     * creating another transaction and without consuming quota. Works for
     * standalone and envelope sessions alike.
     *
     * `expiresAt` is inherited from the original session and is not extended.
     *
     * Authorises the tenant, not the end user. The API cannot tell which of
     * your users is entitled to this link, so an application whose users share
     * one tenant must establish that itself before calling — otherwise this
     * becomes a way for one user to obtain another's signing credential.
     *
     * @param string   $sessionId Session identifier. Must be ACTIVE.
     * @param int|null $timeout   Per-request timeout in milliseconds.
     *
     * @throws \SignDocsBrasil\Api\Errors\ConflictException when the session
     *         is not ACTIVE — a link to a finished session would authenticate
     *         nothing. Use EnvelopesResource::combinedStamp() or
     *         TransactionsResource::download() to reach the signed document.
     */
    public function link(string $sessionId, ?int $timeout = null): MintSigningLinkResponse
    {
        $data = $this->http->request(
            'POST',
            "/v1/signing-sessions/{$sessionId}/link",
            timeout: $timeout,
        );

        return MintSigningLinkResponse::fromArray($data ?? []);
    }

    /**
     * Get full bootstrap data for a signing session.
     *
     * GET /v1/signing-sessions/{sessionId}
     */
    public function get(string $sessionId, ?int $timeout = null): SigningSessionBootstrap
    {
        $data = $this->http->request(
            'GET',
            "/v1/signing-sessions/{$sessionId}",
            timeout: $timeout,
        );

        return SigningSessionBootstrap::fromArray($data ?? []);
    }

    /**
     * Advance a signing session through its steps.
     *
     * POST /v1/signing-sessions/{sessionId}/advance
     */
    public function advance(
        string $sessionId,
        AdvanceSessionRequest $request,
        ?int $timeout = null,
    ): AdvanceSessionResponse {
        $data = $this->http->request(
            'POST',
            "/v1/signing-sessions/{$sessionId}/advance",
            body: $request->toArray(),
            timeout: $timeout,
        );

        return AdvanceSessionResponse::fromArray($data ?? []);
    }

    /**
     * Resend the OTP challenge for a signing session.
     *
     * POST /v1/signing-sessions/{sessionId}/resend-otp
     */
    public function resendOtp(
        string $sessionId,
        ?ResendOtpRequest $request = null,
        ?int $timeout = null,
    ): AdvanceSessionResponse {
        $body = $request?->toArray();

        $data = $this->http->request(
            'POST',
            "/v1/signing-sessions/{$sessionId}/resend-otp",
            body: $body !== null && $body !== [] ? $body : null,
            timeout: $timeout,
        );

        return AdvanceSessionResponse::fromArray($data ?? []);
    }

    /**
     * List signing sessions with optional filters and pagination.
     *
     * GET /v1/signing-sessions
     */
    public function list(?SigningSessionListParams $params = null, ?int $timeout = null): SigningSessionListResponse
    {
        $query = $params?->toArray();

        $data = $this->http->request('GET', '/v1/signing-sessions', query: $query, timeout: $timeout);

        return SigningSessionListResponse::fromArray($data ?? []);
    }

    /**
     * Poll a signing session until it reaches a terminal status (COMPLETED, CANCELLED, EXPIRED).
     *
     * @param string $sessionId      The signing session ID
     * @param int    $pollIntervalMs Milliseconds between polls (default 3000)
     * @param int    $timeoutMs      Maximum milliseconds to wait (default 300000 = 5 min)
     * @return SigningSessionStatus  The final status when a terminal state is reached
     *
     * @throws \RuntimeException If the timeout is exceeded before a terminal status is reached
     */
    public function waitForCompletion(
        string $sessionId,
        int $pollIntervalMs = 3000,
        int $timeoutMs = 300000,
    ): SigningSessionStatus {
        $terminalStatuses = ['COMPLETED', 'CANCELLED', 'EXPIRED'];
        $startTime = hrtime(true);

        while (true) {
            $status = $this->getStatus($sessionId);

            if (in_array($status->status, $terminalStatuses, true)) {
                return $status;
            }

            $elapsedMs = (int) ((hrtime(true) - $startTime) / 1_000_000);
            if ($elapsedMs >= $timeoutMs) {
                throw new \RuntimeException(
                    "Timed out waiting for signing session {$sessionId} to complete. " .
                    "Current status: {$status->status}"
                );
            }

            usleep($pollIntervalMs * 1000);
        }
    }
}
