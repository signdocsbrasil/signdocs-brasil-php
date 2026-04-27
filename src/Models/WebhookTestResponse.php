<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

/**
 * Response of `POST /v1/webhooks/{webhookId}/test`. Carries the
 * webhook ID being tested and the per-attempt {@see WebhookTestDelivery}
 * result describing whether the test endpoint accepted the delivery.
 */
final class WebhookTestResponse
{
    public function __construct(
        public readonly string $webhookId,
        public readonly WebhookTestDelivery $testDelivery,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $delivery = is_array($data['testDelivery'] ?? null) ? $data['testDelivery'] : [];

        return new self(
            webhookId: (string) ($data['webhookId'] ?? ''),
            testDelivery: WebhookTestDelivery::fromArray($delivery),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'webhookId' => $this->webhookId,
            'testDelivery' => $this->testDelivery->toArray(),
        ];
    }
}
