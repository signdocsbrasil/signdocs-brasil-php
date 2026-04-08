<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class HealthCheckResponse
{
    /**
     * @param string                                  $status    Health status (healthy, degraded, unhealthy)
     * @param string                                  $version   API version
     * @param string                                  $timestamp ISO 8601 timestamp
     * @param array<string, array<string, mixed>>|null $services  Service health details
     */
    public function __construct(
        public readonly string $status,
        public readonly string $version,
        public readonly string $timestamp,
        public readonly ?array $services = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: (string) ($data['status'] ?? ''),
            version: (string) ($data['version'] ?? ''),
            timestamp: (string) ($data['timestamp'] ?? ''),
            services: $data['services'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'status' => $this->status,
            'version' => $this->version,
            'timestamp' => $this->timestamp,
        ];

        if ($this->services !== null) {
            $result['services'] = $this->services;
        }

        return $result;
    }
}
