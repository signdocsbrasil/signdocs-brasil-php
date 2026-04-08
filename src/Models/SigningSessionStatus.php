<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class SigningSessionStatus
{
    /**
     * @param string                       $sessionId
     * @param string                       $status
     * @param array<int, array<string, mixed>>  $signers   Per-signer status list
     * @param string                       $updatedAt
     * @param string|null                  $completedAt
     */
    public function __construct(
        public readonly string $sessionId,
        public readonly string $status,
        public readonly array $signers,
        public readonly string $updatedAt,
        public readonly ?string $completedAt = null,
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
            signers: $data['signers'] ?? [],
            updatedAt: (string) ($data['updatedAt'] ?? ''),
            completedAt: isset($data['completedAt']) ? (string) $data['completedAt'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'sessionId' => $this->sessionId,
            'status' => $this->status,
            'signers' => $this->signers,
            'updatedAt' => $this->updatedAt,
        ];

        if ($this->completedAt !== null) {
            $result['completedAt'] = $this->completedAt;
        }

        return $result;
    }
}
