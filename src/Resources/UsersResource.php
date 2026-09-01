<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Resources;

use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\EnrollUserRequest;
use SignDocsBrasil\Api\Models\EnrollUserResponse;
use SignDocsBrasil\Api\Models\EnrollmentStatusResponse;
use SignDocsBrasil\Api\Models\DeleteEnrollmentResponse;

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

    /**
     * Read whether a user is enrolled and, crucially, until when.
     *
     * Use it to sweep your user base and re-enrol before `expired` flips.
     * Nothing warns you on its own beyond the ENROLLMENT.EXPIRING webhook, and
     * once the grace window closes this throws NotFound rather than reporting
     * an expired enrolment.
     *
     * GET /v1/users/{userExternalId}/enrollment
     */
    public function getEnrollment(string $userExternalId, ?int $timeout = null): EnrollmentStatusResponse
    {
        $data = $this->http->request(
            'GET',
            "/v1/users/{$userExternalId}/enrollment",
            timeout: $timeout,
        );

        return EnrollmentStatusResponse::fromArray($data ?? []);
    }

    /**
     * Erase a user's biometric enrolment (LGPD art. 18).
     *
     * Destroys every stored version of the reference image, not just the
     * current one, and removes the record. Irreversible.
     *
     * DELETE /v1/users/{userExternalId}/enrollment
     */
    public function deleteEnrollment(string $userExternalId, ?int $timeout = null): DeleteEnrollmentResponse
    {
        $data = $this->http->request(
            'DELETE',
            "/v1/users/{$userExternalId}/enrollment",
            timeout: $timeout,
        );

        return DeleteEnrollmentResponse::fromArray($data ?? []);
    }
}
