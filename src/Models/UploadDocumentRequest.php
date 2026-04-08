<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class UploadDocumentRequest
{
    /**
     * @param string      $content  Base64-encoded document content
     * @param string|null $filename Original filename
     */
    public function __construct(
        public readonly string $content,
        public readonly ?string $filename = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            content: (string) ($data['content'] ?? ''),
            filename: isset($data['filename']) ? (string) $data['filename'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = ['content' => $this->content];

        if ($this->filename !== null) {
            $result['filename'] = $this->filename;
        }

        return $result;
    }
}
