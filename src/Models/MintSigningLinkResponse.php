<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class MintSigningLinkResponse
{
    /**
     * @param string $sessionId     Signing session identifier.
     * @param string $transactionId Underlying transaction identifier.
     * @param string $url           Single-use signing URL. Treat it as a bearer credential.
     * @param string $expiresAt     ISO 8601 deadline of the original session (UTC); not extended by this call.
     * @param int    $expiresIn     Seconds remaining until $expiresAt.
     */
    public function __construct(
        public readonly string $sessionId,
        public readonly string $transactionId,
        public readonly string $url,
        public readonly string $expiresAt,
        public readonly int $expiresIn,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sessionId: (string) ($data['sessionId'] ?? ''),
            transactionId: (string) ($data['transactionId'] ?? ''),
            url: (string) ($data['url'] ?? ''),
            expiresAt: (string) ($data['expiresAt'] ?? ''),
            expiresIn: (int) ($data['expiresIn'] ?? 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sessionId' => $this->sessionId,
            'transactionId' => $this->transactionId,
            'url' => $this->url,
            'expiresAt' => $this->expiresAt,
            'expiresIn' => $this->expiresIn,
        ];
    }
}
