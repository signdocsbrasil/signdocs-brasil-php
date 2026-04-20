<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\TokenCache;

/**
 * Immutable value object representing a cached OAuth2 access token
 * along with its absolute expiry timestamp.
 */
final class CachedToken
{
    public function __construct(
        public readonly string $accessToken,
        public readonly float $expiresAt,
    ) {
    }

    public function isExpired(float $now, int $skewSeconds = 30): bool
    {
        return $now >= ($this->expiresAt - $skewSeconds);
    }
}
