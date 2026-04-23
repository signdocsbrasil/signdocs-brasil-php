<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class EnvelopeSessionSummary
{
    /**
     * @param string      $sessionId     Unique session identifier.
     * @param string      $transactionId Underlying transaction identifier.
     * @param int         $signerIndex   Index of the signer in the envelope.
     * @param string      $signerName    Display name of the signer.
     * @param string      $status        Session status.
     * @param string|null $completedAt   ISO 8601 completion timestamp (UTC).
     * @param string|null $evidenceId    Evidence identifier, present when status is COMPLETED.
     */
    public function __construct(
        public readonly string $sessionId,
        public readonly string $transactionId,
        public readonly int $signerIndex,
        public readonly string $signerName,
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
            signerIndex: (int) ($data['signerIndex'] ?? 0),
            signerName: (string) ($data['signerName'] ?? ''),
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
            'signerIndex' => $this->signerIndex,
            'signerName' => $this->signerName,
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
