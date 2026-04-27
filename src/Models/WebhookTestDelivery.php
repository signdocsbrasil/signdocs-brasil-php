<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

/**
 * Per-attempt result of a `POST /v1/webhooks/{id}/test` invocation.
 * Embedded inside {@see WebhookTestResponse}.
 */
final class WebhookTestDelivery
{
    public function __construct(
        public readonly int $httpStatus,
        public readonly bool $success,
        public readonly string $timestamp,
        public readonly ?string $error = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            httpStatus: (int) ($data['httpStatus'] ?? 0),
            success: (bool) ($data['success'] ?? false),
            timestamp: (string) ($data['timestamp'] ?? ''),
            error: isset($data['error']) ? (string) $data['error'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'httpStatus' => $this->httpStatus,
            'success' => $this->success,
            'timestamp' => $this->timestamp,
        ];

        if ($this->error !== null) {
            $result['error'] = $this->error;
        }

        return $result;
    }
}
