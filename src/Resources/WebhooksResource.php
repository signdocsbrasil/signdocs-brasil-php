<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Resources;

use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\RegisterWebhookRequest;
use SignDocsBrasil\Api\Models\RegisterWebhookResponse;
use SignDocsBrasil\Api\Models\Webhook;
use SignDocsBrasil\Api\Models\WebhookTestResponse;

final class WebhooksResource
{
    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    /**
     * Register a new webhook endpoint.
     *
     * POST /v1/webhooks (returns 201)
     */
    public function register(RegisterWebhookRequest $request, ?int $timeout = null): RegisterWebhookResponse
    {
        $data = $this->http->request(
            'POST',
            '/v1/webhooks',
            $request->toArray(),
            timeout: $timeout,
        );

        return RegisterWebhookResponse::fromArray($data ?? []);
    }

    /**
     * List all registered webhooks.
     *
     * GET /v1/webhooks
     *
     * @return Webhook[]
     */
    public function list(?int $timeout = null): array
    {
        $data = $this->http->request('GET', '/v1/webhooks', timeout: $timeout);

        if (!is_array($data)) {
            return [];
        }

        // Handle both direct array and wrapped response
        $webhooks = $data['webhooks'] ?? $data;

        return array_map(fn(array $w) => Webhook::fromArray($w), $webhooks);
    }

    /**
     * Delete a webhook endpoint.
     *
     * DELETE /v1/webhooks/{webhookId} (returns 204 No Content)
     */
    public function delete(string $webhookId, ?int $timeout = null): void
    {
        $this->http->request('DELETE', "/v1/webhooks/{$webhookId}", timeout: $timeout);
    }

    /**
     * Send a test event to a webhook endpoint.
     *
     * POST /v1/webhooks/{webhookId}/test
     */
    public function test(string $webhookId, ?int $timeout = null): WebhookTestResponse
    {
        $data = $this->http->request(
            'POST',
            "/v1/webhooks/{$webhookId}/test",
            timeout: $timeout,
        );

        return WebhookTestResponse::fromArray($data ?? []);
    }
}
