<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class DownloadResponse
{
    /**
     * @param string      $transactionId Transaction identifier
     * @param string|null $documentHash  Hash of the document
     * @param string|null $originalUrl   Download URL for the original document
     * @param string|null $signedUrl     Download URL for the signed document
     * @param int         $expiresIn     Expiration time in seconds
     */
    public function __construct(
        public readonly string $transactionId,
        public readonly ?string $documentHash = null,
        public readonly ?string $originalUrl = null,
        public readonly ?string $signedUrl = null,
        public readonly int $expiresIn = 0,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionId: (string) ($data['transactionId'] ?? ''),
            documentHash: isset($data['documentHash']) ? (string) $data['documentHash'] : null,
            originalUrl: isset($data['originalUrl']) ? (string) $data['originalUrl'] : null,
            signedUrl: isset($data['signedUrl']) ? (string) $data['signedUrl'] : null,
            expiresIn: (int) ($data['expiresIn'] ?? 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'transactionId' => $this->transactionId,
            'expiresIn' => $this->expiresIn,
        ];

        if ($this->documentHash !== null) {
            $result['documentHash'] = $this->documentHash;
        }
        if ($this->originalUrl !== null) {
            $result['originalUrl'] = $this->originalUrl;
        }
        if ($this->signedUrl !== null) {
            $result['signedUrl'] = $this->signedUrl;
        }

        return $result;
    }
}
