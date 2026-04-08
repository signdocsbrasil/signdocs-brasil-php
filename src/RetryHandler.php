<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api;

use SignDocsBrasil\Api\Errors\TimeoutException;

/**
 * Exponential backoff retry handler with jitter.
 *
 * Retries on HTTP 429, 500, and 503 responses, respecting
 * the Retry-After header when present.
 */
final class RetryHandler
{
    /** @var int[] HTTP status codes eligible for retry */
    private const RETRYABLE_STATUS_CODES = [429, 500, 503];

    /** Maximum total duration in seconds before giving up */
    private const MAX_TOTAL_DURATION = 60;

    /** Maximum delay between retries in seconds */
    private const MAX_DELAY = 30;

    public function __construct(
        private readonly int $maxRetries,
    ) {
    }

    /**
     * Determine whether a given HTTP status code is retryable.
     */
    public function isRetryable(int $statusCode): bool
    {
        return in_array($statusCode, self::RETRYABLE_STATUS_CODES, true);
    }

    /**
     * Calculate the delay (in microseconds) before the next retry attempt.
     *
     * @param int      $attempt       Zero-based attempt number
     * @param int|null $retryAfterSec Value of the Retry-After header in seconds, if present
     * @return int Delay in microseconds
     */
    public function getDelay(int $attempt, ?int $retryAfterSec = null): int
    {
        if ($retryAfterSec !== null && $retryAfterSec > 0) {
            $delaySec = min($retryAfterSec, self::MAX_DELAY);
            return (int) ($delaySec * 1_000_000);
        }

        // Exponential backoff: 2^attempt seconds + random jitter (0-1 second)
        $baseSec = min((2 ** $attempt), self::MAX_DELAY);
        $jitterSec = mt_rand(0, 1000) / 1000;
        $delaySec = min($baseSec + $jitterSec, self::MAX_DELAY);

        return (int) ($delaySec * 1_000_000);
    }

    /**
     * Check whether another retry attempt should be made.
     *
     * @param int   $attempt  Zero-based attempt number (already completed)
     * @param float $startTime Timestamp (via microtime(true)) of when retrying began
     * @return bool True if another attempt should be made
     * @throws TimeoutException If total duration has been exceeded
     */
    public function shouldRetry(int $attempt, float $startTime): bool
    {
        $elapsed = microtime(true) - $startTime;

        if ($elapsed >= self::MAX_TOTAL_DURATION) {
            throw new TimeoutException(
                'Request exceeded maximum retry duration of 60s'
            );
        }

        return $attempt < $this->maxRetries;
    }
}
