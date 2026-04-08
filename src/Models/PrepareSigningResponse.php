<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class PrepareSigningResponse
{
    public function __construct(
        public readonly string $signatureRequestId,
        public readonly string $hashToSign,
        public readonly string $hashAlgorithm,
        public readonly string $signatureAlgorithm,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            signatureRequestId: (string) ($data['signatureRequestId'] ?? ''),
            hashToSign: (string) ($data['hashToSign'] ?? ''),
            hashAlgorithm: (string) ($data['hashAlgorithm'] ?? 'SHA-256'),
            signatureAlgorithm: (string) ($data['signatureAlgorithm'] ?? 'RSASSA-PKCS1-v1_5'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'signatureRequestId' => $this->signatureRequestId,
            'hashToSign' => $this->hashToSign,
            'hashAlgorithm' => $this->hashAlgorithm,
            'signatureAlgorithm' => $this->signatureAlgorithm,
        ];
    }
}
