<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class CancelSigningSessionResponse
{
    /**
     * @param string $sessionId   Signing session identifier
     * @param string $status      Session status after cancellation
     * @param string $cancelledAt ISO 8601 cancellation timestamp
     */
    public function __construct(
        public readonly string $sessionId,
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
            sessionId: (string) ($data['sessionId'] ?? ''),
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
            'sessionId' => $this->sessionId,
            'status' => $this->status,
            'cancelledAt' => $this->cancelledAt,
        ];
    }
}
