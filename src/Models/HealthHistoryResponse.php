<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class HealthHistoryResponse
{
    /**
     * @param HealthCheckResponse[] $entries
     */
    public function __construct(
        public readonly array $entries,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $entries = [];
        if (isset($data['entries']) && is_array($data['entries'])) {
            foreach ($data['entries'] as $entry) {
                $entries[] = HealthCheckResponse::fromArray($entry);
            }
        }

        return new self(entries: $entries);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'entries' => array_map(
                fn(HealthCheckResponse $e) => $e->toArray(),
                $this->entries,
            ),
        ];
    }
}
