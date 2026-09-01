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
        /**
         * Inspect without writing. Returns the same verdict the batch endpoint
         * gives and persists nothing — no image, no record, and the 90-day
         * retention clock never starts.
         */
        public readonly bool $dryRun = false,
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
        $result = [
            'image' => $this->image,
            'cpf' => $this->cpf,
            'source' => $this->source,
        ];

        if ($this->dryRun) {
            $result['dryRun'] = true;
        }

        return $result;
    }
}
