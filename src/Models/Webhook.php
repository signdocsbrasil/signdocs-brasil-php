<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class Webhook
{
    /**
     * @param string   $webhookId
     * @param string   $url
     * @param string[] $events
     * @param string   $status
     * @param string   $createdAt
     */
    public function __construct(
        public readonly string $webhookId,
        public readonly string $url,
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
            events: array_map('strval', $data['events'] ?? []),
            status: (string) ($data['status'] ?? ''),
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
            'events' => $this->events,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
        ];
    }
}
