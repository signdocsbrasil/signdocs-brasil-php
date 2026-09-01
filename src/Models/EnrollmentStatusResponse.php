<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

/**
 * Reports whether a user is enrolled and, crucially, until when.
 *
 * The reference image is hard-deleted by S3 lifecycle $retentionDays after
 * enrolment, while the record outlives it by a grace period. $expiresAt and
 * $expired are what let an integrator run a re-enrolment sweep instead of
 * discovering the gap as a 422 mid-signature — and the sweep has to happen
 * inside that grace window, because once it passes this route answers 404,
 * which is indistinguishable from "never enrolled".
 */
final class EnrollmentStatusResponse
{
    /**
     * @param string      $expiresAt When the reference image is deleted
     * @param bool        $expired   True once $expiresAt has passed — re-enrol
     * @param string|null $maskedCpf CPF is masked: this route is enumerable by userExternalId
     */
    public function __construct(
        public readonly string $userExternalId,
        public readonly string $enrollmentSource,
        public readonly int $enrollmentVersion,
        public readonly string $enrollmentHash,
        public readonly string $enrolledAt,
        public readonly string $expiresAt,
        public readonly bool $expired,
        public readonly int $retentionDays,
        public readonly ?string $maskedCpf = null,
        public readonly ?float $faceConfidence = null,
        public readonly ?string $documentImageHash = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userExternalId: (string) ($data['userExternalId'] ?? ''),
            enrollmentSource: (string) ($data['enrollmentSource'] ?? ''),
            enrollmentVersion: (int) ($data['enrollmentVersion'] ?? 0),
            enrollmentHash: (string) ($data['enrollmentHash'] ?? ''),
            enrolledAt: (string) ($data['enrolledAt'] ?? ''),
            expiresAt: (string) ($data['expiresAt'] ?? ''),
            expired: (bool) ($data['expired'] ?? false),
            retentionDays: (int) ($data['retentionDays'] ?? 0),
            maskedCpf: isset($data['maskedCpf']) ? (string) $data['maskedCpf'] : null,
            faceConfidence: isset($data['faceConfidence']) ? (float) $data['faceConfidence'] : null,
            documentImageHash: isset($data['documentImageHash']) ? (string) $data['documentImageHash'] : null,
        );
    }
}
