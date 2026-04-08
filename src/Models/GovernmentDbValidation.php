<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class GovernmentDbValidation
{
    /**
     * @param GovernmentDatabase $database              Government database used
     * @param string             $validatedAt           Validation timestamp
     * @param string             $cpfHash               Hashed CPF
     * @param float              $biometricScore        Biometric score
     * @param bool               $cached                Whether result was cached
     * @param float|null         $cacheVerifySimilarity Cache verification similarity
     * @param string|null        $cacheExpiresAt        Cache expiration timestamp
     */
    public function __construct(
        public readonly GovernmentDatabase $database,
        public readonly string $validatedAt,
        public readonly string $cpfHash,
        public readonly float $biometricScore,
        public readonly bool $cached,
        public readonly ?float $cacheVerifySimilarity = null,
        public readonly ?string $cacheExpiresAt = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            database: GovernmentDatabase::from((string) ($data['database'] ?? '')),
            validatedAt: (string) ($data['validatedAt'] ?? ''),
            cpfHash: (string) ($data['cpfHash'] ?? ''),
            biometricScore: (float) ($data['biometricScore'] ?? 0.0),
            cached: (bool) ($data['cached'] ?? false),
            cacheVerifySimilarity: isset($data['cacheVerifySimilarity']) ? (float) $data['cacheVerifySimilarity'] : null,
            cacheExpiresAt: isset($data['cacheExpiresAt']) ? (string) $data['cacheExpiresAt'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'database' => $this->database->value,
            'validatedAt' => $this->validatedAt,
            'cpfHash' => $this->cpfHash,
            'biometricScore' => $this->biometricScore,
            'cached' => $this->cached,
        ];

        if ($this->cacheVerifySimilarity !== null) {
            $result['cacheVerifySimilarity'] = $this->cacheVerifySimilarity;
        }
        if ($this->cacheExpiresAt !== null) {
            $result['cacheExpiresAt'] = $this->cacheExpiresAt;
        }

        return $result;
    }
}
