<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class Policy
{
    /**
     * @param string        $profile     Policy profile (e.g. CLICK_ONLY, BIOMETRIC, DIGITAL_CERTIFICATE, CUSTOM)
     * @param string[]|null $customSteps Custom step types when profile is CUSTOM
     */
    public function __construct(
        public readonly string $profile,
        public readonly ?array $customSteps = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            profile: (string) ($data['profile'] ?? ''),
            customSteps: isset($data['customSteps']) ? array_map('strval', $data['customSteps']) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = ['profile' => $this->profile];

        if ($this->customSteps !== null) {
            $result['customSteps'] = $this->customSteps;
        }

        return $result;
    }
}
