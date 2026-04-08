<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Resources;

use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\ConfirmDocumentRequest;
use SignDocsBrasil\Api\Models\ConfirmDocumentResponse;
use SignDocsBrasil\Api\Models\DocumentUploadResponse;
use SignDocsBrasil\Api\Models\DownloadResponse;
use SignDocsBrasil\Api\Models\PresignRequest;
use SignDocsBrasil\Api\Models\PresignResponse;
use SignDocsBrasil\Api\Models\UploadDocumentRequest;

final class DocumentsResource
{
    public function __construct(
        private readonly HttpClient $http,
    ) {
    }

    /**
     * Upload a document (base64) directly to a transaction.
     *
     * POST /v1/transactions/{transactionId}/document
     */
    public function upload(string $transactionId, UploadDocumentRequest $request, ?int $timeout = null): DocumentUploadResponse
    {
        $data = $this->http->request(
            'POST',
            "/v1/transactions/{$transactionId}/document",
            $request->toArray(),
            timeout: $timeout,
        );

        return DocumentUploadResponse::fromArray($data ?? []);
    }

    /**
     * Get a pre-signed URL for direct document upload.
     *
     * POST /v1/transactions/{transactionId}/document/presign
     */
    public function presign(string $transactionId, PresignRequest $request, ?int $timeout = null): PresignResponse
    {
        $data = $this->http->request(
            'POST',
            "/v1/transactions/{$transactionId}/document/presign",
            $request->toArray(),
            timeout: $timeout,
        );

        return PresignResponse::fromArray($data ?? []);
    }

    /**
     * Confirm a previously uploaded document (via presigned URL).
     *
     * POST /v1/transactions/{transactionId}/document/confirm
     */
    public function confirm(string $transactionId, ConfirmDocumentRequest $request, ?int $timeout = null): ConfirmDocumentResponse
    {
        $data = $this->http->request(
            'POST',
            "/v1/transactions/{$transactionId}/document/confirm",
            $request->toArray(),
            timeout: $timeout,
        );

        return ConfirmDocumentResponse::fromArray($data ?? []);
    }

    /**
     * Get a download URL for the transaction document.
     *
     * GET /v1/transactions/{$transactionId}/download
     */
    public function download(string $transactionId, ?int $timeout = null): DownloadResponse
    {
        $data = $this->http->request(
            'GET',
            "/v1/transactions/{$transactionId}/download",
            timeout: $timeout,
        );

        return DownloadResponse::fromArray($data ?? []);
    }
}
