<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class Policy
{
    /**
     * @param string        $profile               Policy profile (e.g. CLICK_ONLY, BIOMETRIC, DIGITAL_CERTIFICATE, CUSTOM)
     * @param string[]|null $customSteps           Custom step types when profile is CUSTOM
     * @param float|null    $minSimilarity         Minimum facial-match similarity for BIOMETRIC_MATCH /
     *                                             DOCUMENT_PHOTO_MATCH. Tightens only: a value below the tenant's
     *                                             configured threshold is rejected with 400 naming the current
     *                                             minimum, never silently ignored. Percentage (95) or fraction (0.95).
     * @param float|null    $minLivenessConfidence Minimum liveness confidence for BIOMETRIC_LIVENESS. Same rule.
     */
    public function __construct(
        public readonly string $profile,
        public readonly ?array $customSteps = null,
        public readonly ?float $minSimilarity = null,
        public readonly ?float $minLivenessConfidence = null,
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
            minSimilarity: isset($data['minSimilarity']) ? (float) $data['minSimilarity'] : null,
            minLivenessConfidence: isset($data['minLivenessConfidence']) ? (float) $data['minLivenessConfidence'] : null,
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

        if ($this->minSimilarity !== null) {
            $result['minSimilarity'] = $this->minSimilarity;
        }

        if ($this->minLivenessConfidence !== null) {
            $result['minLivenessConfidence'] = $this->minLivenessConfidence;
        }

        return $result;
    }
}
