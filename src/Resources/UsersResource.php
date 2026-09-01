<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Resources;

use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\EnrollUserRequest;
use SignDocsBrasil\Api\Models\EnrollUserResponse;
use SignDocsBrasil\Api\Models\EnrollmentStatusResponse;
use SignDocsBrasil\Api\Models\DeleteEnrollmentResponse;
use SignDocsBrasil\Api\Models\EnrollUsersBatchResponse;

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

    /**
     * Enrol up to 25 users in one request.
     *
     * The documented cap is 25 rows, but the binding limit is the request body —
     * roughly 6MB, and base64 inflates each photo by a third. Keep photos under
     * ~175KB (640x640 is ample) to use all 25 slots.
     *
     * With $dryRun nothing is persisted: every row is inspected and returned
     * with quality metrics, no image reaches storage, and the 90-day retention
     * clock never starts. Rekognition's confidence answers "is this a face?",
     * not "is this a good reference" — a dark, blurred photo enrols happily at
     * 99.99 confidence and fails face matching months later.
     *
     * POST /v1/users/enrollments
     *
     * @param list<array<string, mixed>> $enrollments Rows with userExternalId, image, cpf
     */
    public function enrollBatch(array $enrollments, bool $dryRun = false, ?int $timeout = null): EnrollUsersBatchResponse
    {
        $body = ['enrollments' => array_values($enrollments)];
        if ($dryRun) {
            $body['dryRun'] = true;
        }

        $data = $this->http->request(
            'POST',
            '/v1/users/enrollments',
            $body,
            timeout: $timeout,
        );

        return EnrollUsersBatchResponse::fromArray($data ?? []);
    }
}
