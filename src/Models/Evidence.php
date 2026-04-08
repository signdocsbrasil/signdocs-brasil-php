<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class Evidence
{
    /**
     * @param string                       $tenantId
     * @param string                       $transactionId
     * @param string                       $evidenceId
     * @param string                       $status
     * @param array<string, mixed>         $signer       Signer summary (name, cpf, cnpj, userExternalId)
     * @param array<int, array<string, mixed>> $steps    Evidence steps with type, status, result
     * @param string                       $createdAt
     * @param array<string, mixed>|null    $document     Document info (hash, filename)
     * @param string|null                  $completedAt
     */
    public function __construct(
        public readonly string $tenantId,
        public readonly string $transactionId,
        public readonly string $evidenceId,
        public readonly string $status,
        public readonly array $signer,
        public readonly array $steps,
        public readonly string $createdAt,
        public readonly ?array $document = null,
        public readonly ?string $completedAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: (string) ($data['tenantId'] ?? ''),
            transactionId: (string) ($data['transactionId'] ?? ''),
            evidenceId: (string) ($data['evidenceId'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            signer: $data['signer'] ?? [],
            steps: $data['steps'] ?? [],
            createdAt: (string) ($data['createdAt'] ?? ''),
            document: $data['document'] ?? null,
            completedAt: isset($data['completedAt']) ? (string) $data['completedAt'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'tenantId' => $this->tenantId,
            'transactionId' => $this->transactionId,
            'evidenceId' => $this->evidenceId,
            'status' => $this->status,
            'signer' => $this->signer,
            'steps' => $this->steps,
            'createdAt' => $this->createdAt,
        ];

        if ($this->document !== null) {
            $result['document'] = $this->document;
        }
        if ($this->completedAt !== null) {
            $result['completedAt'] = $this->completedAt;
        }

        return $result;
    }
}
