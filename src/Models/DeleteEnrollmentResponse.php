<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

/**
 * Result of erasing a user's biometric enrolment (LGPD art. 18).
 */
final class DeleteEnrollmentResponse
{
    /**
     * @param int|null $objectsDeleted Objects removed from storage; every version of each is destroyed
     */
    public function __construct(
        public readonly string $userExternalId,
        public readonly bool $deleted,
        public readonly string $deletedAt,
        public readonly ?int $enrollmentVersion = null,
        public readonly ?int $objectsDeleted = null,
        public readonly ?int $versionsDeleted = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userExternalId: (string) ($data['userExternalId'] ?? ''),
            deleted: (bool) ($data['deleted'] ?? false),
            deletedAt: (string) ($data['deletedAt'] ?? ''),
            enrollmentVersion: isset($data['enrollmentVersion']) ? (int) $data['enrollmentVersion'] : null,
            objectsDeleted: isset($data['objectsDeleted']) ? (int) $data['objectsDeleted'] : null,
            versionsDeleted: isset($data['versionsDeleted']) ? (int) $data['versionsDeleted'] : null,
        );
    }
}
