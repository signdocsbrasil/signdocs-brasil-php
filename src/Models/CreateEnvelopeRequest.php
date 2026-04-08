<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class CreateEnvelopeRequest
{
    /**
     * @param string                     $signingMode       Signing mode (e.g. SEQUENTIAL, PARALLEL)
     * @param int                        $totalSigners      Total number of signers expected
     * @param string                     $documentContent   Base64-encoded document content
     * @param string|null                $documentFilename  Original document filename
     * @param string|null                $returnUrl         Redirect URL after signing
     * @param string|null                $cancelUrl         Redirect URL on cancel
     * @param array<string, string>|null $metadata          Arbitrary key-value metadata
     * @param string|null                $locale            Locale for the signing UI
     * @param int|null                   $expiresInMinutes  Custom expiration time in minutes
     */
    public function __construct(
        public readonly string $signingMode,
        public readonly int $totalSigners,
        public readonly string $documentContent,
        public readonly ?string $documentFilename = null,
        public readonly ?string $returnUrl = null,
        public readonly ?string $cancelUrl = null,
        public readonly ?array $metadata = null,
        public readonly ?string $locale = null,
        public readonly ?int $expiresInMinutes = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            signingMode: (string) ($data['signingMode'] ?? ''),
            totalSigners: (int) ($data['totalSigners'] ?? 0),
            documentContent: (string) ($data['documentContent'] ?? ''),
            documentFilename: isset($data['documentFilename']) ? (string) $data['documentFilename'] : null,
            returnUrl: isset($data['returnUrl']) ? (string) $data['returnUrl'] : null,
            cancelUrl: isset($data['cancelUrl']) ? (string) $data['cancelUrl'] : null,
            metadata: $data['metadata'] ?? null,
            locale: isset($data['locale']) ? (string) $data['locale'] : null,
            expiresInMinutes: isset($data['expiresInMinutes']) ? (int) $data['expiresInMinutes'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'signingMode' => $this->signingMode,
            'totalSigners' => $this->totalSigners,
            'documentContent' => $this->documentContent,
        ];

        if ($this->documentFilename !== null) {
            $result['documentFilename'] = $this->documentFilename;
        }
        if ($this->returnUrl !== null) {
            $result['returnUrl'] = $this->returnUrl;
        }
        if ($this->cancelUrl !== null) {
            $result['cancelUrl'] = $this->cancelUrl;
        }
        if ($this->metadata !== null) {
            $result['metadata'] = $this->metadata;
        }
        if ($this->locale !== null) {
            $result['locale'] = $this->locale;
        }
        if ($this->expiresInMinutes !== null) {
            $result['expiresInMinutes'] = $this->expiresInMinutes;
        }

        return $result;
    }
}
