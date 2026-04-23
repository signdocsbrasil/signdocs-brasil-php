<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class Envelope
{
    /**
     * @param string $envelopeId   Unique envelope identifier.
     * @param string $status       Envelope status (CREATED, ACTIVE, COMPLETED, CANCELLED, EXPIRED).
     * @param string $signingMode  Signing mode (PARALLEL or SEQUENTIAL).
     * @param int    $totalSigners Total number of signers expected.
     * @param string $documentHash SHA-256 hash of the envelope document.
     * @param string $createdAt    ISO 8601 creation timestamp (UTC).
     * @param string $expiresAt    ISO 8601 expiration timestamp (UTC).
     */
    public function __construct(
        public readonly string $envelopeId,
        public readonly string $status,
        public readonly string $signingMode,
        public readonly int $totalSigners,
        public readonly string $documentHash,
        public readonly string $createdAt,
        public readonly string $expiresAt,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            envelopeId: (string) ($data['envelopeId'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            signingMode: (string) ($data['signingMode'] ?? ''),
            totalSigners: (int) ($data['totalSigners'] ?? 0),
            documentHash: (string) ($data['documentHash'] ?? ''),
            createdAt: (string) ($data['createdAt'] ?? ''),
            expiresAt: (string) ($data['expiresAt'] ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'envelopeId' => $this->envelopeId,
            'status' => $this->status,
            'signingMode' => $this->signingMode,
            'totalSigners' => $this->totalSigners,
            'documentHash' => $this->documentHash,
            'createdAt' => $this->createdAt,
            'expiresAt' => $this->expiresAt,
        ];
    }
}
