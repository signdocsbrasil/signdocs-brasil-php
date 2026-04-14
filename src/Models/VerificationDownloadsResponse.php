<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class VerificationDownloadsResponse
{
    /**
     * @param string               $evidenceId Evidence identifier
     * @param array<string, mixed> $downloads  Download URLs grouped by type:
     *                                         `originalDocument`, `evidencePack`, `finalPdf` (each may be `null`),
     *                                         and `signedSignature` (only present for standalone signing
     *                                         sessions; omitted for evidences belonging to a multi-signer
     *                                         envelope — use `VerificationResource::verifyEnvelope()` for the
     *                                         consolidated envelope-level `.p7s`).
     */
    public function __construct(
        public readonly string $evidenceId,
        public readonly array $downloads,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            evidenceId: (string) ($data['evidenceId'] ?? ''),
            downloads: $data['downloads'] ?? [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'evidenceId' => $this->evidenceId,
            'downloads' => $this->downloads,
        ];
    }
}
