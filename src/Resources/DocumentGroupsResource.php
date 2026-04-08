<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Resources;

use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\CombinedStampResponse;

final class DocumentGroupsResource
{
    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    /**
     * Generate a combined stamp for all documents in a group.
     *
     * POST /v1/document-groups/{documentGroupId}/combined-stamp
     */
    public function combinedStamp(string $documentGroupId, ?int $timeout = null): CombinedStampResponse
    {
        $data = $this->http->request(
            'POST',
            "/v1/document-groups/{$documentGroupId}/combined-stamp",
            timeout: $timeout,
        );

        return CombinedStampResponse::fromArray($data ?? []);
    }
}
