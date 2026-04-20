<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\TokenCache;

/**
 * Pluggable cache for OAuth2 access tokens.
 *
 * Default implementation is {@see InMemoryTokenCache}, which scopes the
 * cache to the lifetime of a single PHP process. Long-lived daemons and
 * daemon-like workloads can keep using the default. Stateless hosts
 * (PHP-FPM, lambda, serverless) should supply an implementation backed
 * by a shared store (APCu, Redis, WordPress transients, etc.) to avoid
 * fetching a fresh token on every request.
 *
 * Implementations MUST be safe to call concurrently — i.e. a set() that
 * races with another set() for the same key should leave the cache in a
 * consistent state. Implementations SHOULD treat the key as opaque; the
 * SDK derives keys deterministically from credentials + base URL.
 */
interface TokenCacheInterface
{
    /**
     * Retrieve a cached token for $key, or null if missing or expired.
     * Implementations SHOULD return null (not throw) on any backend error.
     */
    public function get(string $key): ?CachedToken;

    /**
     * Store $token under $key. Implementations SHOULD honor the token's
     * expiresAt as the storage TTL upper bound.
     */
    public function set(string $key, CachedToken $token): void;

    /**
     * Remove the cached token for $key. Idempotent: deleting a missing
     * entry is a no-op.
     */
    public function delete(string $key): void;
}
