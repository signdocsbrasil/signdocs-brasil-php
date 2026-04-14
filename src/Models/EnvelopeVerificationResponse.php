<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

/**
 * Public verification response for a multi-signer envelope, returned by
 * `GET /v1/verify/envelope/{envelopeId}`.
 *
 * For non-PDF envelopes signed with digital certificates, the consolidated
 * `.p7s` containing every signer's `SignerInfo` is exposed via
 * `$downloads['consolidatedSignature']`.
 */
final class EnvelopeVerificationResponse
{
    /**
     * @param string                            $envelopeId        Envelope identifier
     * @param string                            $status            Envelope status (CREATED|ACTIVE|COMPLETED|...)
     * @param string                            $signingMode       PARALLEL or SEQUENTIAL
     * @param int                               $totalSigners      Number of signers in the envelope
     * @param int                               $completedSessions Number of signers already completed
     * @param string                            $documentHash      Hash of the envelope document
     * @param string|null                       $tenantName        Tenant display name
     * @param string|null                       $tenantCnpj        Tenant CNPJ
     * @param array<int, array<string, mixed>>  $signers           Per-signer entries (with optional evidenceId)
     * @param array<string, mixed>              $downloads         Consolidated downloads:
     *                                                             `combinedSignedPdf` (PDF envelopes) and/or
     *                                                             `consolidatedSignature` (non-PDF envelopes)
     * @param string                            $createdAt         ISO 8601 creation timestamp
     * @param string|null                       $completedAt       ISO 8601 completion timestamp
     */
    public function __construct(
        public readonly string $envelopeId,
        public readonly string $status,
        public readonly string $signingMode,
        public readonly int $totalSigners,
        public readonly int $completedSessions,
        public readonly string $documentHash,
        public readonly array $signers,
        public readonly array $downloads,
        public readonly string $createdAt,
        public readonly ?string $tenantName = null,
        public readonly ?string $tenantCnpj = null,
        public readonly ?string $completedAt = null,
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
            completedSessions: (int) ($data['completedSessions'] ?? 0),
            documentHash: (string) ($data['documentHash'] ?? ''),
            signers: is_array($data['signers'] ?? null) ? $data['signers'] : [],
            downloads: is_array($data['downloads'] ?? null) ? $data['downloads'] : [],
            createdAt: (string) ($data['createdAt'] ?? ''),
            tenantName: isset($data['tenantName']) ? (string) $data['tenantName'] : null,
            tenantCnpj: isset($data['tenantCnpj']) ? (string) $data['tenantCnpj'] : null,
            completedAt: isset($data['completedAt']) ? (string) $data['completedAt'] : null,
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
            'completedSessions' => $this->completedSessions,
            'documentHash' => $this->documentHash,
            'signers' => $this->signers,
            'downloads' => $this->downloads,
            'createdAt' => $this->createdAt,
        ];

        if ($this->tenantName !== null) {
            $result['tenantName'] = $this->tenantName;
        }
        if ($this->tenantCnpj !== null) {
            $result['tenantCnpj'] = $this->tenantCnpj;
        }
        if ($this->completedAt !== null) {
            $result['completedAt'] = $this->completedAt;
        }

        return $result;
    }
}
