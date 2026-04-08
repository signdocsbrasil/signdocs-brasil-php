<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class ConfirmDocumentRequest
{
    /**
     * @param string $uploadToken Upload token returned by the presign endpoint
     */
    public function __construct(
        public readonly string $uploadToken,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            uploadToken: (string) ($data['uploadToken'] ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'uploadToken' => $this->uploadToken,
        ];
    }
}
