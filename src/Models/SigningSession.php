<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class SigningSession
{
    /**
     * @param string                       $sessionId
     * @param string                       $tenantId
     * @param string                       $name
     * @param string                       $type
     * @param string                       $status
     * @param array<int, array<string, mixed>>  $signers
     * @param array<int, array<string, mixed>>  $documents
     * @param string                       $createdAt
     * @param string                       $updatedAt
     * @param string|null                  $callbackUrl
     * @param string|null                  $redirectUrl
     * @param string|null                  $sessionUrl
     * @param array<string, string>|null   $metadata
     * @param string|null                  $locale
     * @param string|null                  $brandingId
     * @param string|null                  $expiresAt
     */
    public function __construct(
        public readonly string $sessionId,
        public readonly string $tenantId,
        public readonly string $name,
        public readonly string $type,
        public readonly string $status,
        public readonly array $signers,
        public readonly array $documents,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?string $callbackUrl = null,
        public readonly ?string $redirectUrl = null,
        public readonly ?string $sessionUrl = null,
        public readonly ?array $metadata = null,
        public readonly ?string $locale = null,
        public readonly ?string $brandingId = null,
        public readonly ?string $expiresAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sessionId: (string) ($data['sessionId'] ?? ''),
            tenantId: (string) ($data['tenantId'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            type: (string) ($data['type'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            signers: $data['signers'] ?? [],
            documents: $data['documents'] ?? [],
            createdAt: (string) ($data['createdAt'] ?? ''),
            updatedAt: (string) ($data['updatedAt'] ?? ''),
            callbackUrl: isset($data['callbackUrl']) ? (string) $data['callbackUrl'] : null,
            redirectUrl: isset($data['redirectUrl']) ? (string) $data['redirectUrl'] : null,
            sessionUrl: isset($data['sessionUrl']) ? (string) $data['sessionUrl'] : null,
            metadata: $data['metadata'] ?? null,
            locale: isset($data['locale']) ? (string) $data['locale'] : null,
            brandingId: isset($data['brandingId']) ? (string) $data['brandingId'] : null,
            expiresAt: isset($data['expiresAt']) ? (string) $data['expiresAt'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'sessionId' => $this->sessionId,
            'tenantId' => $this->tenantId,
            'name' => $this->name,
            'type' => $this->type,
            'status' => $this->status,
            'signers' => $this->signers,
            'documents' => $this->documents,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];

        if ($this->callbackUrl !== null) {
            $result['callbackUrl'] = $this->callbackUrl;
        }
        if ($this->redirectUrl !== null) {
            $result['redirectUrl'] = $this->redirectUrl;
        }
        if ($this->sessionUrl !== null) {
            $result['sessionUrl'] = $this->sessionUrl;
        }
        if ($this->metadata !== null) {
            $result['metadata'] = $this->metadata;
        }
        if ($this->locale !== null) {
            $result['locale'] = $this->locale;
        }
        if ($this->brandingId !== null) {
            $result['brandingId'] = $this->brandingId;
        }
        if ($this->expiresAt !== null) {
            $result['expiresAt'] = $this->expiresAt;
        }

        return $result;
    }
}
