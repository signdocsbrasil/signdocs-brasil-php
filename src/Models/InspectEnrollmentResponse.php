<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

/**
 * Verdict for one candidate reference photo, from a dry run.
 *
 * `marginal` is the one to act on: it would enrol without complaint and is
 * exactly what becomes a rejected signature later.
 */
final class InspectEnrollmentResponse
{
    /**
     * @param string             $status   usable | marginal | rejected
     * @param list<string>       $warnings Quality advisories; empty when clean
     * @param array<string,mixed>|null $quality Rekognition measures, 0-100
     * @param array<string,mixed>|null $pose    Head rotation in degrees
     */
    public function __construct(
        public readonly string $status,
        public readonly array $warnings = [],
        public readonly bool $dryRun = true,
        public readonly ?string $userExternalId = null,
        public readonly ?string $error = null,
        public readonly ?float $faceConfidence = null,
        public readonly ?array $quality = null,
        public readonly ?array $pose = null,
        public readonly ?float $faceCoverage = null,
        /** Same field a real enrolment returns. In a dry run it equals $status. */
        public readonly ?string $referenceQuality = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: (string) ($data['status'] ?? ''),
            warnings: array_values((array) ($data['warnings'] ?? [])),
            dryRun: (bool) ($data['dryRun'] ?? true),
            userExternalId: isset($data['userExternalId']) ? (string) $data['userExternalId'] : null,
            error: isset($data['error']) ? (string) $data['error'] : null,
            faceConfidence: isset($data['faceConfidence']) ? (float) $data['faceConfidence'] : null,
            quality: $data['quality'] ?? null,
            pose: $data['pose'] ?? null,
            faceCoverage: isset($data['faceCoverage']) ? (float) $data['faceCoverage'] : null,
            referenceQuality: isset($data['referenceQuality']) ? (string) $data['referenceQuality'] : null,
        );
    }
}
