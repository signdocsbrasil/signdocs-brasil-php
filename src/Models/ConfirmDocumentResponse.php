<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class ConfirmDocumentResponse
{
    /**
     * @param string $transactionId Transaction identifier
     * @param string $status        Document confirmation status
     * @param string $documentHash  Hash of the confirmed document
     */
    public function __construct(
        public readonly string $transactionId,
        public readonly string $status,
        public readonly string $documentHash,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionId: (string) ($data['transactionId'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            documentHash: (string) ($data['documentHash'] ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'transactionId' => $this->transactionId,
            'status' => $this->status,
            'documentHash' => $this->documentHash,
        ];
    }
}
