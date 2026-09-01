<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

/**
 * Result of a batch enrollment.
 *
 * Partial success is the point, so this comes back 200 even when rows failed:
 * one unusable photo must not reject the other twenty-four. Read $results, not
 * the HTTP status.
 */
final class EnrollUsersBatchResponse
{
    /**
     * Advisory reasons a reference photo is usable but weak. A row carrying any
     * of these enrols without complaint today and is exactly what fails face
     * matching later, which is the whole reason the dry run exists.
     */
    public const WARNING_LOW_BRIGHTNESS = 'LOW_BRIGHTNESS';
    public const WARNING_LOW_SHARPNESS = 'LOW_SHARPNESS';
    public const WARNING_FACE_TOO_SMALL = 'FACE_TOO_SMALL';
    public const WARNING_HEAD_TURNED = 'HEAD_TURNED';

    /**
     * @param list<array<string, mixed>> $results  Per-row outcomes. `status` is
     *                                            enrolled/failed on a real write and
     *                                            usable/marginal/rejected on a dry run;
     *                                            `marginal` is the one to act on, since it
     *                                            enrols without complaint today and is
     *                                            exactly what fails matching later.
     * @param int|null                   $usable   Dry runs only
     * @param int|null                   $marginal Dry runs only
     * @param int|null                   $rejected Dry runs only
     */
    public function __construct(
        public readonly int $submitted,
        public readonly array $results,
        public readonly ?int $succeeded = null,
        public readonly ?int $failed = null,
        public readonly ?bool $dryRun = null,
        public readonly ?int $usable = null,
        public readonly ?int $marginal = null,
        public readonly ?int $rejected = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            submitted: (int) ($data['submitted'] ?? 0),
            results: array_values((array) ($data['results'] ?? [])),
            succeeded: isset($data['succeeded']) ? (int) $data['succeeded'] : null,
            failed: isset($data['failed']) ? (int) $data['failed'] : null,
            dryRun: isset($data['dryRun']) ? (bool) $data['dryRun'] : null,
            usable: isset($data['usable']) ? (int) $data['usable'] : null,
            marginal: isset($data['marginal']) ? (int) $data['marginal'] : null,
            rejected: isset($data['rejected']) ? (int) $data['rejected'] : null,
        );
    }
}
