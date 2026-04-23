<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class EnvelopeDetail
{
    /**
     * @param string                            $envelopeId           Unique envelope identifier.
     * @param string                            $status               Envelope status (CREATED, ACTIVE, COMPLETED, CANCELLED, EXPIRED).
     * @param string                            $signingMode          Signing mode (PARALLEL or SEQUENTIAL).
     * @param int                               $totalSigners         Total number of signers expected.
     * @param int                               $addedSessions        Number of sessions added so far.
     * @param int                               $completedSessions    Number of sessions completed.
     * @param string                            $documentHash         SHA-256 hash of the document.
     * @param array<int, EnvelopeSessionSummary> $sessions            Session summaries.
     * @param string                            $createdAt            ISO 8601 creation timestamp (UTC).
     * @param string                            $updatedAt            ISO 8601 last update timestamp (UTC).
     * @param string                            $expiresAt            ISO 8601 expiration timestamp (UTC).
     * @param string|null                       $combinedSignedPdfUrl URL for the combined signed PDF, present after all signers complete.
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
        public readonly string $expiresAt,
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
            expiresAt: (string) ($data['expiresAt'] ?? ''),
            combinedSignedPdfUrl: isset($data['combinedSignedPdfUrl']) ? (string) $data['combinedSignedPdfUrl'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'envelopeId' => $this->envelopeId,
            'status' => $this->status,
            'signingMode' => $this->signingMode,
            'totalSigners' => $this->totalSigners,
            'addedSessions' => $this->addedSessions,
            'completedSessions' => $this->completedSessions,
            'documentHash' => $this->documentHash,
            'sessions' => array_map(
                static fn(EnvelopeSessionSummary $s): array => $s->toArray(),
                $this->sessions,
            ),
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'expiresAt' => $this->expiresAt,
        ];

        if ($this->combinedSignedPdfUrl !== null) {
            $result['combinedSignedPdfUrl'] = $this->combinedSignedPdfUrl;
        }

        return $result;
    }
}
