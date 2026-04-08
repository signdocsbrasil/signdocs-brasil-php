<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class FinalizeResponse
{
    /**
     * @param string $transactionId Transaction identifier
     * @param string $status        Transaction status after finalization
     * @param string $evidenceId    Evidence identifier
     * @param string $evidenceHash  Hash of the evidence pack
     * @param string $completedAt   ISO 8601 completion timestamp
     */
    public function __construct(
        public readonly string $transactionId,
        public readonly string $status,
        public readonly string $evidenceId,
        public readonly string $evidenceHash,
        public readonly string $completedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionId: (string) ($data['transactionId'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            evidenceId: (string) ($data['evidenceId'] ?? ''),
            evidenceHash: (string) ($data['evidenceHash'] ?? ''),
            completedAt: (string) ($data['completedAt'] ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'transactionId' => $this->transactionId,
            'status' => $this->status,
            'evidenceId' => $this->evidenceId,
            'evidenceHash' => $this->evidenceHash,
            'completedAt' => $this->completedAt,
        ];
    }
}
