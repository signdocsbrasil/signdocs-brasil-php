<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class EnrollUserRequest
{
    /**
     * @param string $image  Base64-encoded JPEG reference image
     * @param string $cpf    CPF number (11 digits)
     * @param string $source Image source (BANK_PROVIDED, FIRST_LIVENESS, DOCUMENT_PHOTO)
     */
    public function __construct(
        public readonly string $image,
        public readonly string $cpf,
        public readonly string $source = 'BANK_PROVIDED',
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            image: (string) ($data['image'] ?? ''),
            cpf: (string) ($data['cpf'] ?? ''),
            source: (string) ($data['source'] ?? 'BANK_PROVIDED'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'image' => $this->image,
            'cpf' => $this->cpf,
            'source' => $this->source,
        ];
    }
}
