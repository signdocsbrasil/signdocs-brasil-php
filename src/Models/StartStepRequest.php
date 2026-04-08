<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class StartStepRequest
{
    /**
     * @param string|null $captureMode Capture mode (BANK_APP or HOSTED_PAGE)
     * @param string|null $otpChannel  OTP delivery channel (email or sms)
     */
    public function __construct(
        public readonly ?string $captureMode = null,
        public readonly ?string $otpChannel = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            captureMode: isset($data['captureMode']) ? (string) $data['captureMode'] : null,
            otpChannel: isset($data['otpChannel']) ? (string) $data['otpChannel'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];

        if ($this->captureMode !== null) {
            $result['captureMode'] = $this->captureMode;
        }
        if ($this->otpChannel !== null) {
            $result['otpChannel'] = $this->otpChannel;
        }

        return $result;
    }
}
