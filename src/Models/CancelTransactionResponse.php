<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class CancelTransactionResponse
{
    /**
     * @param string $transactionId Transaction identifier
     * @param string $status        Transaction status after cancellation
     * @param string $cancelledAt   ISO 8601 cancellation timestamp
     */
    public function __construct(
        public readonly string $transactionId,
        public readonly string $status,
        public readonly string $cancelledAt,
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
            cancelledAt: (string) ($data['cancelledAt'] ?? ''),
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
            'cancelledAt' => $this->cancelledAt,
        ];
    }
}
