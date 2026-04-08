<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class EnvelopeSession
{
    /**
     * @param string      $sessionId     Unique session identifier
     * @param string      $transactionId Associated transaction identifier
     * @param int         $signerIndex   Index of the signer in the envelope
     * @param string      $status        Session status
     * @param string      $url           Signing URL for the signer
     * @param string      $clientSecret  Client secret for frontend integration
     * @param string|null $expiresAt     ISO 8601 expiration timestamp
     */
    public function __construct(
        public readonly string $sessionId,
        public readonly string $transactionId,
        public readonly int $signerIndex,
        public readonly string $status,
        public readonly string $url,
        public readonly string $clientSecret,
        public readonly ?string $expiresAt = null,
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
            signerIndex: (int) ($data['signerIndex'] ?? 0),
            status: (string) ($data['status'] ?? ''),
            url: (string) ($data['url'] ?? ''),
            clientSecret: (string) ($data['clientSecret'] ?? ''),
            expiresAt: isset($data['expiresAt']) ? (string) $data['expiresAt'] : null,
        );
    }
}
