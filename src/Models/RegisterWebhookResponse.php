<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class RegisterWebhookResponse
{
    /**
     * @param string   $webhookId
     * @param string   $url
     * @param string   $secret    HMAC secret for webhook signature verification
     * @param string[] $events
     * @param string   $status    Always 'ACTIVE' on creation
     * @param string   $createdAt ISO 8601 timestamp
     */
    public function __construct(
        public readonly string $webhookId,
        public readonly string $url,
        public readonly string $secret,
        public readonly array $events,
        public readonly string $status,
        public readonly string $createdAt,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            webhookId: (string) ($data['webhookId'] ?? ''),
            url: (string) ($data['url'] ?? ''),
            secret: (string) ($data['secret'] ?? ''),
            events: array_map('strval', $data['events'] ?? []),
            status: (string) ($data['status'] ?? 'ACTIVE'),
            createdAt: (string) ($data['createdAt'] ?? ''),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'webhookId' => $this->webhookId,
            'url' => $this->url,
            'secret' => $this->secret,
            'events' => $this->events,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
        ];
    }
}
