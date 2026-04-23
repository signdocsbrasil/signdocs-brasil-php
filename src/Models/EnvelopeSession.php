<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class EnvelopeSession
{
    /**
     * @param string    $sessionId     Unique session identifier.
     * @param string    $transactionId Underlying transaction identifier.
     * @param int       $signerIndex   Index of the signer in the envelope.
     * @param string    $status        Session status.
     * @param string    $url           Hosted signing page URL.
     * @param string    $clientSecret  Session secret for the widget/redirect flow.
     * @param string    $expiresAt     ISO 8601 expiration timestamp (UTC).
     * @param bool|null $inviteSent    True when SignDocs dispatched an invitation email to the signer at the time this session was added. Populated only when the envelope was created with an `owner` and the signer's email differs from the owner's (case-insensitive).
     */
    public function __construct(
        public readonly string $sessionId,
        public readonly string $transactionId,
        public readonly int $signerIndex,
        public readonly string $status,
        public readonly string $url,
        public readonly string $clientSecret,
        public readonly string $expiresAt,
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
            signerIndex: (int) ($data['signerIndex'] ?? 0),
            status: (string) ($data['status'] ?? ''),
            url: (string) ($data['url'] ?? ''),
            clientSecret: (string) ($data['clientSecret'] ?? ''),
            expiresAt: (string) ($data['expiresAt'] ?? ''),
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
            'signerIndex' => $this->signerIndex,
            'status' => $this->status,
            'url' => $this->url,
            'clientSecret' => $this->clientSecret,
            'expiresAt' => $this->expiresAt,
        ];

        if ($this->inviteSent !== null) {
            $result['inviteSent'] = $this->inviteSent;
        }

        return $result;
    }
}
