<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api;

use Psr\Http\Message\ResponseInterface;

/**
 * Captures response-level metadata that's typically consumed for
 * observability and lifecycle signaling: rate-limit counters
 * (IETF RateLimit headers), RFC 8594 deprecation signaling, and
 * the upstream request ID.
 *
 * Exposed via the `onResponse` callback in {@see Config}. The SDK
 * does not otherwise surface these headers to resource methods, so
 * the callback is the single place to plug in observability.
 */
final class ResponseMetadata
{
    /**
     * @param int|null              $rateLimitLimit     From `RateLimit-Limit`
     * @param int|null              $rateLimitRemaining From `RateLimit-Remaining`
     * @param int|null              $rateLimitReset     From `RateLimit-Reset` (seconds from now)
     * @param \DateTimeImmutable|null $deprecation      Parsed `Deprecation` header (RFC 8594)
     * @param \DateTimeImmutable|null $sunset           Parsed `Sunset` header (RFC 8594)
     * @param string|null           $requestId          Upstream `X-Request-Id` or `X-SignDocs-Request-Id`
     * @param int                   $statusCode         HTTP status code
     * @param string                $method             HTTP method (uppercased)
     * @param string                $path               Request path (with query string if any)
     */
    public function __construct(
        public readonly ?int $rateLimitLimit,
        public readonly ?int $rateLimitRemaining,
        public readonly ?int $rateLimitReset,
        public readonly ?\DateTimeImmutable $deprecation,
        public readonly ?\DateTimeImmutable $sunset,
        public readonly ?string $requestId,
        public readonly int $statusCode,
        public readonly string $method,
        public readonly string $path,
    ) {
    }

    public static function fromResponse(
        ResponseInterface $response,
        string $method,
        string $path,
    ): self {
        return new self(
            rateLimitLimit: self::intHeader($response, 'RateLimit-Limit'),
            rateLimitRemaining: self::intHeader($response, 'RateLimit-Remaining'),
            rateLimitReset: self::intHeader($response, 'RateLimit-Reset'),
            deprecation: self::rfc8594Date($response->getHeaderLine('Deprecation')),
            sunset: self::rfc8594Date($response->getHeaderLine('Sunset')),
            requestId: self::firstHeader($response, ['X-Request-Id', 'X-SignDocs-Request-Id']),
            statusCode: $response->getStatusCode(),
            method: strtoupper($method),
            path: $path,
        );
    }

    /**
     * True if the endpoint is marked deprecated (has a Deprecation header).
     */
    public function isDeprecated(): bool
    {
        return $this->deprecation !== null;
    }

    private static function intHeader(ResponseInterface $response, string $name): ?int
    {
        $value = $response->getHeaderLine($name);
        if ($value === '') {
            return null;
        }
        if (!preg_match('/^-?\d+$/', $value)) {
            return null;
        }
        return (int) $value;
    }

    /**
     * @param list<string> $names
     */
    private static function firstHeader(ResponseInterface $response, array $names): ?string
    {
        foreach ($names as $name) {
            $value = $response->getHeaderLine($name);
            if ($value !== '') {
                return $value;
            }
        }
        return null;
    }

    /**
     * Parse an RFC 8594 Deprecation/Sunset header. Accepts either an
     * IMF-fixdate (HTTP-date) or an `@<unix-seconds>` form. Returns
     * null for any unparseable input.
     */
    private static function rfc8594Date(string $raw): ?\DateTimeImmutable
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if ($raw[0] === '@' && preg_match('/^@(-?\d+)$/', $raw, $m)) {
            try {
                return new \DateTimeImmutable('@' . $m[1]);
            } catch (\Exception) {
                return null;
            }
        }

        try {
            $parsed = new \DateTimeImmutable($raw);
        } catch (\Exception) {
            return null;
        }

        return $parsed;
    }
}
