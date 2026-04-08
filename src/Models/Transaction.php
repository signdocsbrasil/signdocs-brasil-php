<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class Transaction
{
    /**
     * @param string                       $tenantId
     * @param string                       $transactionId
     * @param string                       $status
     * @param string                       $purpose
     * @param Policy                       $policy
     * @param Signer                       $signer
     * @param Step[]                       $steps
     * @param string                       $expiresAt
     * @param string                       $createdAt
     * @param string                       $updatedAt
     * @param string|null                  $documentGroupId
     * @param int|null                     $signerIndex
     * @param int|null                     $totalSigners
     * @param array<string, string>|null   $metadata
     * @param string|null                  $submissionDeadline
     * @param string|null                  $deadlineStatus
     */
    public function __construct(
        public readonly string $tenantId,
        public readonly string $transactionId,
        public readonly string $status,
        public readonly string $purpose,
        public readonly Policy $policy,
        public readonly Signer $signer,
        public readonly array $steps,
        public readonly string $expiresAt,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?string $documentGroupId = null,
        public readonly ?int $signerIndex = null,
        public readonly ?int $totalSigners = null,
        public readonly ?array $metadata = null,
        public readonly ?string $submissionDeadline = null,
        public readonly ?string $deadlineStatus = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $steps = [];
        if (isset($data['steps']) && is_array($data['steps'])) {
            foreach ($data['steps'] as $step) {
                $steps[] = Step::fromArray($step);
            }
        }

        return new self(
            tenantId: (string) ($data['tenantId'] ?? ''),
            transactionId: (string) ($data['transactionId'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            purpose: (string) ($data['purpose'] ?? ''),
            policy: Policy::fromArray($data['policy'] ?? []),
            signer: Signer::fromArray($data['signer'] ?? []),
            steps: $steps,
            expiresAt: (string) ($data['expiresAt'] ?? ''),
            createdAt: (string) ($data['createdAt'] ?? ''),
            updatedAt: (string) ($data['updatedAt'] ?? ''),
            documentGroupId: isset($data['documentGroupId']) ? (string) $data['documentGroupId'] : null,
            signerIndex: isset($data['signerIndex']) ? (int) $data['signerIndex'] : null,
            totalSigners: isset($data['totalSigners']) ? (int) $data['totalSigners'] : null,
            metadata: $data['metadata'] ?? null,
            submissionDeadline: isset($data['submissionDeadline']) ? (string) $data['submissionDeadline'] : null,
            deadlineStatus: isset($data['deadlineStatus']) ? (string) $data['deadlineStatus'] : null,
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
            'status' => $this->status,
            'purpose' => $this->purpose,
            'policy' => $this->policy->toArray(),
            'signer' => $this->signer->toArray(),
            'steps' => array_map(fn(Step $s) => $s->toArray(), $this->steps),
            'expiresAt' => $this->expiresAt,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];

        if ($this->documentGroupId !== null) {
            $result['documentGroupId'] = $this->documentGroupId;
        }
        if ($this->signerIndex !== null) {
            $result['signerIndex'] = $this->signerIndex;
        }
        if ($this->totalSigners !== null) {
            $result['totalSigners'] = $this->totalSigners;
        }
        if ($this->metadata !== null) {
            $result['metadata'] = $this->metadata;
        }
        if ($this->submissionDeadline !== null) {
            $result['submissionDeadline'] = $this->submissionDeadline;
        }
        if ($this->deadlineStatus !== null) {
            $result['deadlineStatus'] = $this->deadlineStatus;
        }

        return $result;
    }
}
