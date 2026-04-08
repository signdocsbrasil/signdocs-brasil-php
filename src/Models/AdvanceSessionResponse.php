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
        );
    }
}
