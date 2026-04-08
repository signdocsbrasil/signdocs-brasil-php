<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class RegisterWebhookRequest
{
    /**
     * @param string   $url    Webhook endpoint URL
     * @param string[] $events Event types to subscribe to
     */
    public function __construct(
        public readonly string $url,
        public readonly array $events,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            url: (string) ($data['url'] ?? ''),
            events: array_map('strval', $data['events'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'events' => $this->events,
        ];
    }
}
