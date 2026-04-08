<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class CompleteSigningResponse
{
    /**
     * @param string               $stepId
     * @param string               $status
     * @param array<string, mixed> $result Contains 'digitalSignature' sub-object
     */
    public function __construct(
        public readonly string $stepId,
        public readonly string $status,
        public readonly array $result,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            stepId: (string) ($data['stepId'] ?? ''),
            status: (string) ($data['status'] ?? ''),
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
            'status' => $this->status,
            'result' => $this->result,
        ];
    }
}
