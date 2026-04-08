<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class EnvelopeDetail
{
    /**
     * @param string                        $envelopeId          Unique envelope identifier
     * @param string                        $status              Envelope status
     * @param string                        $signingMode         Signing mode (e.g. SEQUENTIAL, PARALLEL)
     * @param int                           $totalSigners        Total number of signers expected
     * @param int                           $addedSessions       Number of sessions added so far
     * @param int                           $completedSessions   Number of sessions completed
     * @param string                        $documentHash        SHA-256 hash of the document
     * @param array<int, EnvelopeSessionSummary> $sessions       Session summaries
     * @param string                        $createdAt           ISO 8601 creation timestamp
     * @param string                        $updatedAt           ISO 8601 last update timestamp
     * @param string|null                   $expiresAt           ISO 8601 expiration timestamp
     * @param string|null                   $combinedSignedPdfUrl URL for the combined signed PDF
     */
    public function __construct(
        public readonly string $envelopeId,
        public readonly string $status,
        public readonly string $signingMode,
        public readonly int $totalSigners,
        public readonly int $addedSessions,
        public readonly int $completedSessions,
        public readonly string $documentHash,
        public readonly array $sessions,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?string $expiresAt = null,
        public readonly ?string $combinedSignedPdfUrl = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $sessions = array_map(
            static fn(array $s): EnvelopeSessionSummary => EnvelopeSessionSummary::fromArray($s),
            $data['sessions'] ?? [],
        );

        return new self(
            envelopeId: (string) ($data['envelopeId'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            signingMode: (string) ($data['signingMode'] ?? ''),
            totalSigners: (int) ($data['totalSigners'] ?? 0),
            addedSessions: (int) ($data['addedSessions'] ?? 0),
            completedSessions: (int) ($data['completedSessions'] ?? 0),
            documentHash: (string) ($data['documentHash'] ?? ''),
            sessions: $sessions,
            createdAt: (string) ($data['createdAt'] ?? ''),
            updatedAt: (string) ($data['updatedAt'] ?? ''),
            expiresAt: isset($data['expiresAt']) ? (string) $data['expiresAt'] : null,
            combinedSignedPdfUrl: isset($data['combinedSignedPdfUrl']) ? (string) $data['combinedSignedPdfUrl'] : null,
        );
    }
}
