<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class WebhookTestResponse
{
    public function __construct(
        public readonly string $deliveryId,
        public readonly string $status,
        public readonly ?int $statusCode = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            deliveryId: (string) ($data['deliveryId'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            statusCode: isset($data['statusCode']) ? (int) $data['statusCode'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'deliveryId' => $this->deliveryId,
            'status' => $this->status,
        ];

        if ($this->statusCode !== null) {
            $result['statusCode'] = $this->statusCode;
        }

        return $result;
    }
}
