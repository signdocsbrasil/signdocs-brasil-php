<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SignDocsBrasil\Api\Errors\TimeoutException;
use SignDocsBrasil\Api\RetryHandler;

final class RetryHandlerTest extends TestCase
{
    private RetryHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new RetryHandler(3);
    }

    public static function retryableCodesProvider(): array
    {
        return [[429], [500], [503]];
    }

    #[DataProvider('retryableCodesProvider')]
    public function testIsRetryableForRetryableCodes(int $status): void
    {
        $this->assertTrue($this->handler->isRetryable($status));
    }

    public static function nonRetryableCodesProvider(): array
    {
        return [[200], [201], [204], [400], [401], [403], [404], [409], [422]];
    }

    #[DataProvider('nonRetryableCodesProvider')]
    public function testIsNotRetryableForOtherCodes(int $status): void
    {
        $this->assertFalse($this->handler->isRetryable($status));
    }

    public function testGetDelayWithRetryAfter(): void
    {
        $delay = $this->handler->getDelay(0, 5);
        $this->assertSame(5_000_000, $delay); // 5 seconds in microseconds
    }

    public function testGetDelayExponentialBackoff(): void
    {
        $delay = $this->handler->getDelay(0);
        // 2^0 = 1 second + jitter (0-1s) = 1-2s = 1_000_000 - 2_000_000 microseconds
        $this->assertGreaterThanOrEqual(1_000_000, $delay);
        $this->assertLessThanOrEqual(2_001_000, $delay);
    }

    public function testGetDelayMaxCap(): void
    {
        $delay = $this->handler->getDelay(10); // 2^10 = 1024, capped at 30
        $maxMicroseconds = 30_000_000;
        $this->assertLessThanOrEqual($maxMicroseconds, $delay);
    }

    public function testShouldRetryWithinLimit(): void
    {
        $this->assertTrue($this->handler->shouldRetry(0, microtime(true)));
        $this->assertTrue($this->handler->shouldRetry(2, microtime(true)));
    }

    public function testShouldRetryExceedsMaxRetries(): void
    {
        $this->assertFalse($this->handler->shouldRetry(3, microtime(true)));
    }

    public function testShouldRetryThrowsOnTimeout(): void
    {
        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('60s');

        // Simulate start time 61 seconds ago
        $this->handler->shouldRetry(0, microtime(true) - 61);
    }
}
