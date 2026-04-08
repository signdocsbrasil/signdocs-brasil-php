<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class Step
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $transactionId,
        public readonly string $stepId,
        public readonly string $type,
        public readonly string $status,
        public readonly int $order,
        public readonly int $attempts,
        public readonly int $maxAttempts,
        public readonly ?string $captureMode = null,
        public readonly ?string $startedAt = null,
        public readonly ?string $completedAt = null,
        public readonly ?StepResult $result = null,
        public readonly ?string $error = null,
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
            stepId: (string) ($data['stepId'] ?? ''),
            type: (string) ($data['type'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            order: (int) ($data['order'] ?? 0),
            attempts: (int) ($data['attempts'] ?? 0),
            maxAttempts: (int) ($data['maxAttempts'] ?? 0),
            captureMode: isset($data['captureMode']) ? (string) $data['captureMode'] : null,
            startedAt: isset($data['startedAt']) ? (string) $data['startedAt'] : null,
            completedAt: isset($data['completedAt']) ? (string) $data['completedAt'] : null,
            result: isset($data['result']) ? StepResult::fromArray($data['result']) : null,
            error: isset($data['error']) ? (string) $data['error'] : null,
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
            'stepId' => $this->stepId,
            'type' => $this->type,
            'status' => $this->status,
            'order' => $this->order,
            'attempts' => $this->attempts,
            'maxAttempts' => $this->maxAttempts,
        ];

        if ($this->captureMode !== null) {
            $result['captureMode'] = $this->captureMode;
        }
        if ($this->startedAt !== null) {
            $result['startedAt'] = $this->startedAt;
        }
        if ($this->completedAt !== null) {
            $result['completedAt'] = $this->completedAt;
        }
        if ($this->result !== null) {
            $result['result'] = $this->result->toArray();
        }
        if ($this->error !== null) {
            $result['error'] = $this->error;
        }

        return $result;
    }
}
