<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Resources;

use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\HealthCheckResponse;
use SignDocsBrasil\Api\Models\HealthHistoryResponse;

final class HealthResource
{
    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    /**
     * Check API health status.
     *
     * GET /health (no authentication required)
     */
    public function check(?int $timeout = null): HealthCheckResponse
    {
        $data = $this->http->request('GET', '/health', noAuth: true, timeout: $timeout);

        return HealthCheckResponse::fromArray($data ?? []);
    }

    /**
     * Get API health history.
     *
     * GET /health/history (no authentication required)
     */
    public function history(?int $timeout = null): HealthHistoryResponse
    {
        $data = $this->http->request('GET', '/health/history', noAuth: true, timeout: $timeout);

        return HealthHistoryResponse::fromArray($data ?? []);
    }
}
