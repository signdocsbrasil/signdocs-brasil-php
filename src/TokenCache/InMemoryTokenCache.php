<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\TokenCache;

/**
 * Default in-process token cache. Equivalent to the behavior the SDK
 * shipped with in 1.2.x and earlier — cache lives for the lifetime of
 * the PHP process.
 */
final class InMemoryTokenCache implements TokenCacheInterface
{
    /** @var array<string, CachedToken> */
    private array $store = [];

    public function get(string $key): ?CachedToken
    {
        $entry = $this->store[$key] ?? null;
        if ($entry === null) {
            return null;
        }

        if ($entry->isExpired(microtime(true), skewSeconds: 0)) {
            unset($this->store[$key]);
            return null;
        }

        return $entry;
    }

    public function set(string $key, CachedToken $token): void
    {
        $this->store[$key] = $token;
    }

    public function delete(string $key): void
    {
        unset($this->store[$key]);
    }
}
