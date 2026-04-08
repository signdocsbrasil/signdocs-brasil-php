<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class StepCompleteResponse
{
    /**
     * @param string               $stepId   Step identifier
     * @param string               $type     Step type (e.g. click, otp, liveness, biometric_match)
     * @param string               $status   Step status after completion
     * @param int                  $attempts Number of attempts made
     * @param array<string, mixed> $result   Step completion result details
     */
    public function __construct(
        public readonly string $stepId,
        public readonly string $type,
        public readonly string $status,
        public readonly int $attempts,
        public readonly array $result = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            stepId: (string) ($data['stepId'] ?? ''),
            type: (string) ($data['type'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            attempts: (int) ($data['attempts'] ?? 0),
            result: $data['result'] ?? [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'stepId' => $this->stepId,
            'type' => $this->type,
            'status' => $this->status,
            'attempts' => $this->attempts,
            'result' => $this->result,
        ];
    }
}
