<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class Envelope
{
    /**
     * @param string      $envelopeId   Unique envelope identifier
     * @param string      $status       Envelope status
     * @param string      $signingMode  Signing mode (e.g. SEQUENTIAL, PARALLEL)
     * @param int         $totalSigners Total number of signers expected
     * @param string      $documentHash SHA-256 hash of the document
     * @param string      $createdAt    ISO 8601 creation timestamp
     * @param string|null $expiresAt    ISO 8601 expiration timestamp
     */
    public function __construct(
        public readonly string $envelopeId,
        public readonly string $status,
        public readonly string $signingMode,
        public readonly int $totalSigners,
        public readonly string $documentHash,
        public readonly string $createdAt,
        public readonly ?string $expiresAt = null,
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
            expiresAt: isset($data['expiresAt']) ? (string) $data['expiresAt'] : null,
        );
    }
}
