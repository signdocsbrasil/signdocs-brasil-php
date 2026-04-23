<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class SigningSessionStatus
{
    /**
     * @param string      $sessionId     Unique session identifier.
     * @param string      $transactionId Underlying transaction identifier.
     * @param string      $status        Session status (ACTIVE, COMPLETED, CANCELLED, EXPIRED, FAILED).
     * @param string|null $completedAt   ISO 8601 completion timestamp (UTC), present when status is COMPLETED.
     * @param string|null $evidenceId    Evidence identifier, present when status is COMPLETED.
     */
    public function __construct(
        public readonly string $sessionId,
        public readonly string $transactionId,
        public readonly string $status,
        public readonly ?string $completedAt = null,
        public readonly ?string $evidenceId = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sessionId: (string) ($data['sessionId'] ?? ''),
            transactionId: (string) ($data['transactionId'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            completedAt: isset($data['completedAt']) ? (string) $data['completedAt'] : null,
            evidenceId: isset($data['evidenceId']) ? (string) $data['evidenceId'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'sessionId' => $this->sessionId,
            'transactionId' => $this->transactionId,
            'status' => $this->status,
        ];

        if ($this->completedAt !== null) {
            $result['completedAt'] = $this->completedAt;
        }
        if ($this->evidenceId !== null) {
            $result['evidenceId'] = $this->evidenceId;
        }

        return $result;
    }
}
