<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class SigningSession
{
    /**
     * @param string      $sessionId     Unique session identifier.
     * @param string      $transactionId Underlying transaction identifier.
     * @param string      $status        Session status (ACTIVE, COMPLETED, CANCELLED, EXPIRED, FAILED).
     * @param string      $url           Hosted signing page URL.
     * @param string      $clientSecret  Session secret used by the widget/redirect flow.
     * @param string      $expiresAt     ISO 8601 expiration timestamp (UTC).
     * @param string      $createdAt     ISO 8601 creation timestamp (UTC).
     * @param bool|null   $inviteSent    True when SignDocs dispatched an invitation email to `signer.email` at session creation. Populated only when `owner` was provided and `signer.email` differs from `owner.email` (case-insensitive).
     */
    public function __construct(
        public readonly string $sessionId,
        public readonly string $transactionId,
        public readonly string $status,
        public readonly string $url,
        public readonly string $clientSecret,
        public readonly string $expiresAt,
        public readonly string $createdAt,
        public readonly ?bool $inviteSent = null,
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
            status: (string) ($data['status'] ?? ''),
            url: (string) ($data['url'] ?? ''),
            clientSecret: (string) ($data['clientSecret'] ?? ''),
            expiresAt: (string) ($data['expiresAt'] ?? ''),
            createdAt: (string) ($data['createdAt'] ?? ''),
            inviteSent: isset($data['inviteSent']) ? (bool) $data['inviteSent'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'sessionId' => $this->sessionId,
            'transactionId' => $this->transactionId,
            'status' => $this->status,
            'url' => $this->url,
            'clientSecret' => $this->clientSecret,
            'expiresAt' => $this->expiresAt,
            'createdAt' => $this->createdAt,
        ];

        if ($this->inviteSent !== null) {
            $result['inviteSent'] = $this->inviteSent;
        }

        return $result;
    }
}
