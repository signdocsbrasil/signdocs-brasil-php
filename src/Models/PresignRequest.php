<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class PresignRequest
{
    /**
     * @param string $contentType MIME type of the document to upload
     * @param string $filename    Original filename of the document
     */
    public function __construct(
        public readonly string $contentType,
        public readonly string $filename,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            contentType: (string) ($data['contentType'] ?? ''),
            filename: (string) ($data['filename'] ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'contentType' => $this->contentType,
            'filename' => $this->filename,
        ];
    }
}
