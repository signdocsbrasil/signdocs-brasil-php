<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class VerificationResponse
{
    /**
     * @param string                $evidenceId     Evidence identifier
     * @param string                $status         Verification status
     * @param string                $transactionId  Transaction identifier
     * @param string                $purpose        Transaction purpose
     * @param string|null           $documentHash   Hash of the document
     * @param string|null           $evidenceHash   Hash of the evidence pack
     * @param array<string, mixed>  $policy         Verification policy details
     * @param array<string, mixed>  $signer         Signer summary (displayName, cpfCnpj)
     * @param array<array<string, mixed>> $steps    Verification steps
     * @param string|null           $tenantName     Tenant name
     * @param string|null           $tenantCnpj     Tenant CNPJ
     * @param string|null           $createdAt      ISO 8601 creation timestamp
     * @param string|null           $completedAt    ISO 8601 completion timestamp
     */
    public function __construct(
        public readonly string $evidenceId,
        public readonly string $status,
        public readonly string $transactionId,
        public readonly string $purpose,
        public readonly ?string $documentHash = null,
        public readonly ?string $evidenceHash = null,
        public readonly array $policy = [],
        public readonly array $signer = [],
        public readonly array $steps = [],
        public readonly ?string $tenantName = null,
        public readonly ?string $tenantCnpj = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $completedAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            evidenceId: (string) ($data['evidenceId'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            transactionId: (string) ($data['transactionId'] ?? ''),
            purpose: (string) ($data['purpose'] ?? ''),
            documentHash: isset($data['documentHash']) ? (string) $data['documentHash'] : null,
            evidenceHash: isset($data['evidenceHash']) ? (string) $data['evidenceHash'] : null,
            policy: is_array($data['policy'] ?? null) ? $data['policy'] : (isset($data['policy']) ? ['profile' => (string) $data['policy']] : []),
            signer: $data['signer'] ?? [],
            steps: $data['steps'] ?? [],
            tenantName: isset($data['tenantName']) ? (string) $data['tenantName'] : null,
            tenantCnpj: isset($data['tenantCnpj']) ? (string) $data['tenantCnpj'] : null,
            createdAt: isset($data['createdAt']) ? (string) $data['createdAt'] : null,
            completedAt: isset($data['completedAt']) ? (string) $data['completedAt'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'evidenceId' => $this->evidenceId,
            'status' => $this->status,
            'transactionId' => $this->transactionId,
            'purpose' => $this->purpose,
            'policy' => $this->policy,
            'signer' => $this->signer,
            'steps' => $this->steps,
        ];

        if ($this->documentHash !== null) {
            $result['documentHash'] = $this->documentHash;
        }
        if ($this->evidenceHash !== null) {
            $result['evidenceHash'] = $this->evidenceHash;
        }
        if ($this->tenantName !== null) {
            $result['tenantName'] = $this->tenantName;
        }
        if ($this->tenantCnpj !== null) {
            $result['tenantCnpj'] = $this->tenantCnpj;
        }
        if ($this->createdAt !== null) {
            $result['createdAt'] = $this->createdAt;
        }
        if ($this->completedAt !== null) {
            $result['completedAt'] = $this->completedAt;
        }

        return $result;
    }
}
