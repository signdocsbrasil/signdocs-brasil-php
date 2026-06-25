<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

/**
 * Response for `POST /v1/verify/document` — the result of inspecting a PDF
 * for embedded signatures.
 */
final class VerifyDocumentResponse
{
    /**
     * @param bool                          $signed         Whether any signature was detected in the document
     * @param int                           $signatureCount Number of signatures detected
     * @param array<int, DetectedSignature> $signatures     Detected signatures
     * @param string                        $checkedAt      ISO 8601 timestamp of when the document was checked
     */
    public function __construct(
        public readonly bool $signed,
        public readonly int $signatureCount,
        public readonly array $signatures,
        public readonly string $checkedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $signatures = [];
        foreach (($data['signatures'] ?? []) as $signature) {
            if (is_array($signature)) {
                $signatures[] = DetectedSignature::fromArray($signature);
            }
        }

        return new self(
            signed: (bool) ($data['signed'] ?? false),
            signatureCount: (int) ($data['signatureCount'] ?? 0),
            signatures: $signatures,
            checkedAt: (string) ($data['checkedAt'] ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'signed' => $this->signed,
            'signatureCount' => $this->signatureCount,
            'signatures' => array_map(
                static fn (DetectedSignature $signature): array => $signature->toArray(),
                $this->signatures,
            ),
            'checkedAt' => $this->checkedAt,
        ];
    }
}
