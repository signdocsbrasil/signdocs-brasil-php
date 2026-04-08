<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class CompleteSigningRequest
{
    public function __construct(
        public readonly string $signatureRequestId,
        public readonly string $rawSignatureBase64,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            signatureRequestId: (string) ($data['signatureRequestId'] ?? ''),
            rawSignatureBase64: (string) ($data['rawSignatureBase64'] ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'signatureRequestId' => $this->signatureRequestId,
            'rawSignatureBase64' => $this->rawSignatureBase64,
        ];
    }
}
