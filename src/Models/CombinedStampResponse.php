<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class CombinedStampResponse
{
    /**
     * @param string $groupId     Document group identifier
     * @param int    $signerCount Number of signers in the group
     * @param string $downloadUrl Download URL for the stamped document
     * @param int    $expiresIn   Expiration time in seconds
     */
    public function __construct(
        public readonly string $groupId,
        public readonly int $signerCount,
        public readonly string $downloadUrl,
        public readonly int $expiresIn,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            groupId: (string) ($data['groupId'] ?? ''),
            signerCount: (int) ($data['signerCount'] ?? 0),
            downloadUrl: (string) ($data['downloadUrl'] ?? ''),
            expiresIn: (int) ($data['expiresIn'] ?? 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'groupId' => $this->groupId,
            'signerCount' => $this->signerCount,
            'downloadUrl' => $this->downloadUrl,
            'expiresIn' => $this->expiresIn,
        ];
    }
}
