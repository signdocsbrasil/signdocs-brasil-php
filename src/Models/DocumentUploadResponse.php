<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class DocumentUploadResponse
{
    /**
     * @param string $transactionId Transaction identifier
     * @param string $documentHash  Hash of the uploaded document
     * @param string $status        Document upload status
     * @param string $uploadedAt    ISO 8601 upload timestamp
     */
    public function __construct(
        public readonly string $transactionId,
        public readonly string $documentHash,
        public readonly string $status,
        public readonly string $uploadedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionId: (string) ($data['transactionId'] ?? ''),
            documentHash: (string) ($data['documentHash'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            uploadedAt: (string) ($data['uploadedAt'] ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'transactionId' => $this->transactionId,
            'documentHash' => $this->documentHash,
            'status' => $this->status,
            'uploadedAt' => $this->uploadedAt,
        ];
    }
}
