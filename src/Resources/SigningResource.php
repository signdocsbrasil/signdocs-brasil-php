<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Resources;

use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\CompleteSigningRequest;
use SignDocsBrasil\Api\Models\CompleteSigningResponse;
use SignDocsBrasil\Api\Models\PrepareSigningRequest;
use SignDocsBrasil\Api\Models\PrepareSigningResponse;

final class SigningResource
{
    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    /**
     * Prepare a digital signature by providing the certificate chain.
     * Returns the hash to sign and the signature request ID.
     *
     * POST /v1/transactions/{transactionId}/signing/prepare
     */
    public function prepare(
        string $transactionId,
        PrepareSigningRequest $request,
        ?int $timeout = null,
    ): PrepareSigningResponse {
        $data = $this->http->request(
            'POST',
            "/v1/transactions/{$transactionId}/signing/prepare",
            $request->toArray(),
            timeout: $timeout,
        );

        return PrepareSigningResponse::fromArray($data ?? []);
    }

    /**
     * Complete a digital signature by providing the raw signed hash.
     *
     * POST /v1/transactions/{transactionId}/signing/complete
     */
    public function complete(
        string $transactionId,
        CompleteSigningRequest $request,
        ?int $timeout = null,
    ): CompleteSigningResponse {
        $data = $this->http->request(
            'POST',
            "/v1/transactions/{$transactionId}/signing/complete",
            $request->toArray(),
            timeout: $timeout,
        );

        return CompleteSigningResponse::fromArray($data ?? []);
    }
}
