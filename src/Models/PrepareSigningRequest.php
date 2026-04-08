<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class PrepareSigningRequest
{
    /**
     * @param string[] $certificateChainPems PEM-encoded certificate chain
     */
    public function __construct(
        public readonly array $certificateChainPems,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            certificateChainPems: array_map('strval', $data['certificateChainPems'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'certificateChainPems' => $this->certificateChainPems,
        ];
    }
}
