<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class AdvanceSessionResponse
{
    /**
     * @param string                       $sessionId
     * @param string                       $status
     * @param array<string, mixed>|null    $currentStep
     * @param array<string, mixed>|null    $nextStep
     * @param string|null                  $evidenceId
     * @param string|null                  $redirectUrl
     * @param string|null                  $completedAt
     * @param string|null                  $hostedUrl
     * @param string|null                  $livenessSessionId
     * @param string|null                  $signatureRequestId
     * @param string|null                  $hashToSign
     * @param string|null                  $hashAlgorithm
     * @param string|null                  $signatureAlgorithm
     * @param array<string, mixed>|null    $sandbox
     */
    public function __construct(
        public readonly string $sessionId,
        public readonly string $status,
        public readonly ?array $currentStep = null,
        public readonly ?array $nextStep = null,
        public readonly ?string $evidenceId = null,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $completedAt = null,
        public readonly ?string $hostedUrl = null,
        public readonly ?string $livenessSessionId = null,
        public readonly ?string $signatureRequestId = null,
        public readonly ?string $hashToSign = null,
        public readonly ?string $hashAlgorithm = null,
        public readonly ?string $signatureAlgorithm = null,
        public readonly ?array $sandbox = null,
        /**
         * Why a step was rejected, when the step fails but the *request* does
         * not. This is the part that matters most in a biometric integration:
         * a rejected step comes back 200 with the session still ACTIVE and the
         * reason here, not as an HTTP error — code that only catches
         * exceptions reads a rejection as success.
         *
         * Emitted today: BIOMETRIC_MATCH_FAILED, LIVENESS_NOT_COMPLETED,
         * DOCUMENT_QUALITY_LOW, DOCUMENT_MATCH_FAILED and the SERPRO_* family.
         */
        public readonly ?string $errorCode = null,
        /** pt-BR text addressed to the signer, ready to display. */
        public readonly ?string $errorDetail = null,
        /**
         * True while the step has attempts left. Once they run out the step
         * goes FAILED and this is false — the signal that retrying will not
         * help. Each retry is billed as overage.
         */
        public readonly ?bool $retryable = null,
        /** Set when the policy diverted to an alternative step. */
        public readonly ?array $fallback = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sessionId: (string) ($data['sessionId'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            currentStep: $data['currentStep'] ?? null,
            nextStep: $data['nextStep'] ?? null,
            evidenceId: isset($data['evidenceId']) ? (string) $data['evidenceId'] : null,
            redirectUrl: isset($data['redirectUrl']) ? (string) $data['redirectUrl'] : null,
            completedAt: isset($data['completedAt']) ? (string) $data['completedAt'] : null,
            hostedUrl: isset($data['hostedUrl']) ? (string) $data['hostedUrl'] : null,
            livenessSessionId: isset($data['livenessSessionId']) ? (string) $data['livenessSessionId'] : null,
            signatureRequestId: isset($data['signatureRequestId']) ? (string) $data['signatureRequestId'] : null,
            hashToSign: isset($data['hashToSign']) ? (string) $data['hashToSign'] : null,
            hashAlgorithm: isset($data['hashAlgorithm']) ? (string) $data['hashAlgorithm'] : null,
            signatureAlgorithm: isset($data['signatureAlgorithm']) ? (string) $data['signatureAlgorithm'] : null,
            sandbox: $data['sandbox'] ?? null,
            errorCode: isset($data['errorCode']) ? (string) $data['errorCode'] : null,
            errorDetail: isset($data['errorDetail']) ? (string) $data['errorDetail'] : null,
            retryable: isset($data['retryable']) ? (bool) $data['retryable'] : null,
            fallback: $data['fallback'] ?? null,
        );
    }
}
