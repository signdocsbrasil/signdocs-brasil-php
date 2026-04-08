<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Resources;

use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\EnrollUserRequest;
use SignDocsBrasil\Api\Models\EnrollUserResponse;

final class UsersResource
{
    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    /**
     * Enroll a user with a biometric reference image.
     *
     * PUT /v1/users/{userExternalId}/enrollment
     */
    public function enroll(string $userExternalId, EnrollUserRequest $request, ?int $timeout = null): EnrollUserResponse
    {
        $data = $this->http->request(
            'PUT',
            "/v1/users/{$userExternalId}/enrollment",
            $request->toArray(),
            timeout: $timeout,
        );

        return EnrollUserResponse::fromArray($data ?? []);
    }
}
