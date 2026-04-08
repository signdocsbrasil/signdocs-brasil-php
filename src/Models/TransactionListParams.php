<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class TransactionListParams
{
    public function __construct(
        public readonly ?string $status = null,
        public readonly ?string $userExternalId = null,
        public readonly ?string $documentGroupId = null,
        public readonly ?int $limit = null,
        public readonly ?string $nextToken = null,
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: isset($data['status']) ? (string) $data['status'] : null,
            userExternalId: isset($data['userExternalId']) ? (string) $data['userExternalId'] : null,
            documentGroupId: isset($data['documentGroupId']) ? (string) $data['documentGroupId'] : null,
            limit: isset($data['limit']) ? (int) $data['limit'] : null,
            nextToken: isset($data['nextToken']) ? (string) $data['nextToken'] : null,
            startDate: isset($data['startDate']) ? (string) $data['startDate'] : null,
            endDate: isset($data['endDate']) ? (string) $data['endDate'] : null,
        );
    }

    /**
     * @return array<string, string|int|null>
     */
    public function toArray(): array
    {
        return array_filter([
            'status' => $this->status,
            'userExternalId' => $this->userExternalId,
            'documentGroupId' => $this->documentGroupId,
            'limit' => $this->limit,
            'nextToken' => $this->nextToken,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ], fn($v) => $v !== null);
    }
}
