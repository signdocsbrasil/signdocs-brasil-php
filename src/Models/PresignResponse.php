<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class PresignResponse
{
    /**
     * @param string $uploadUrl    Pre-signed upload URL
     * @param string $uploadToken  Token to confirm the upload later
     * @param string $s3Key        S3 object key for the upload
     * @param int    $expiresIn    Expiration time in seconds
     * @param string $contentType  Expected content type for the upload
     * @param string $instructions Human-readable upload instructions
     */
    public function __construct(
        public readonly string $uploadUrl,
        public readonly string $uploadToken,
        public readonly string $s3Key,
        public readonly int $expiresIn,
        public readonly string $contentType,
        public readonly string $instructions,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            uploadUrl: (string) ($data['uploadUrl'] ?? ''),
            uploadToken: (string) ($data['uploadToken'] ?? ''),
            s3Key: (string) ($data['s3Key'] ?? ''),
            expiresIn: (int) ($data['expiresIn'] ?? 0),
            contentType: (string) ($data['contentType'] ?? ''),
            instructions: (string) ($data['instructions'] ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'uploadUrl' => $this->uploadUrl,
            'uploadToken' => $this->uploadToken,
            's3Key' => $this->s3Key,
            'expiresIn' => $this->expiresIn,
            'contentType' => $this->contentType,
            'instructions' => $this->instructions,
        ];
    }
}
