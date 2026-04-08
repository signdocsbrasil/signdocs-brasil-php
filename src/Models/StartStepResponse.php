<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class StartStepResponse
{
    public function __construct(
        public readonly string $stepId,
        public readonly string $type,
        public readonly string $status,
        public readonly ?string $livenessSessionId = null,
        public readonly ?string $hostedUrl = null,
        public readonly ?string $message = null,
        public readonly ?string $otpCode = null,
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
            livenessSessionId: isset($data['livenessSessionId']) ? (string) $data['livenessSessionId'] : null,
            hostedUrl: isset($data['hostedUrl']) ? (string) $data['hostedUrl'] : null,
            message: isset($data['message']) ? (string) $data['message'] : null,
            otpCode: isset($data['otpCode']) ? (string) $data['otpCode'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'stepId' => $this->stepId,
            'type' => $this->type,
            'status' => $this->status,
        ];

        if ($this->livenessSessionId !== null) {
            $result['livenessSessionId'] = $this->livenessSessionId;
        }
        if ($this->hostedUrl !== null) {
            $result['hostedUrl'] = $this->hostedUrl;
        }
        if ($this->message !== null) {
            $result['message'] = $this->message;
        }
        if ($this->otpCode !== null) {
            $result['otpCode'] = $this->otpCode;
        }

        return $result;
    }
}
