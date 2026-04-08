<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class EnvelopeCombinedStampResponse
{
    /**
     * @param string $envelopeId  Unique envelope identifier
     * @param string $downloadUrl Pre-signed download URL for the combined stamped PDF
     * @param int    $expiresIn   Seconds until the download URL expires
     * @param int    $signerCount Number of signers included in the combined stamp
     */
    public function __construct(
        public readonly string $envelopeId,
        public readonly string $downloadUrl,
        public readonly int $expiresIn,
        public readonly int $signerCount,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            envelopeId: (string) ($data['envelopeId'] ?? ''),
            downloadUrl: (string) ($data['downloadUrl'] ?? ''),
            expiresIn: (int) ($data['expiresIn'] ?? 0),
            signerCount: (int) ($data['signerCount'] ?? 0),
        );
    }
}
