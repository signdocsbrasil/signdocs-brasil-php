<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Resources;

use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\StartStepRequest;
use SignDocsBrasil\Api\Models\StartStepResponse;
use SignDocsBrasil\Api\Models\StepCompleteResponse;
use SignDocsBrasil\Api\Models\StepListResponse;

final class StepsResource
{
    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    /**
     * List all steps for a transaction.
     *
     * GET /v1/transactions/{transactionId}/steps
     */
    public function list(string $transactionId, ?int $timeout = null): StepListResponse
    {
        $data = $this->http->request(
            'GET',
            "/v1/transactions/{$transactionId}/steps",
            timeout: $timeout,
        );

        return StepListResponse::fromArray($data ?? []);
    }

    /**
     * Start a specific step in a transaction.
     *
     * POST /v1/transactions/{transactionId}/steps/{stepId}/start
     */
    public function start(
        string $transactionId,
        string $stepId,
        ?StartStepRequest $request = null,
        ?int $timeout = null,
    ): StartStepResponse {
        $body = $request?->toArray() ?? [];

        $data = $this->http->request(
            'POST',
            "/v1/transactions/{$transactionId}/steps/{$stepId}/start",
            $body,
            timeout: $timeout,
        );

        return StartStepResponse::fromArray($data ?? []);
    }

    /**
     * Complete a specific step in a transaction.
     *
     * POST /v1/transactions/{transactionId}/steps/{stepId}/complete
     *
     * The request body varies by step type (click, OTP, liveness, biometric match).
     *
     * @param array<string, mixed>|null $request Step completion data
     */
    public function complete(
        string $transactionId,
        string $stepId,
        ?array $request = null,
        ?int $timeout = null,
    ): StepCompleteResponse {
        $body = $request ?? [];

        $data = $this->http->request(
            'POST',
            "/v1/transactions/{$transactionId}/steps/{$stepId}/complete",
            $body,
            timeout: $timeout,
        );

        return StepCompleteResponse::fromArray($data ?? []);
    }
}
