<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class ResendOtpRequest
{
    /**
     * @param string|null $channel OTP delivery channel (sms, email, whatsapp)
     */
    public function __construct(
        public readonly ?string $channel = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];

        if ($this->channel !== null) {
            $result['channel'] = $this->channel;
        }

        return $result;
    }
}
