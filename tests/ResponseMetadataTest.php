<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Tests;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SignDocsBrasil\Api\ResponseMetadata;

final class ResponseMetadataTest extends TestCase
{
    public function testParsesRateLimitHeaders(): void
    {
        $response = new Response(200, [
            'RateLimit-Limit' => '2000',
            'RateLimit-Remaining' => '1987',
            'RateLimit-Reset' => '42',
        ]);

        $meta = ResponseMetadata::fromResponse($response, 'GET', '/v1/transactions');

        $this->assertSame(2000, $meta->rateLimitLimit);
        $this->assertSame(1987, $meta->rateLimitRemaining);
        $this->assertSame(42, $meta->rateLimitReset);
    }

    public function testMissingHeadersAreNull(): void
    {
        $response = new Response(200);
        $meta = ResponseMetadata::fromResponse($response, 'GET', '/v1/health');

        $this->assertNull($meta->rateLimitLimit);
        $this->assertNull($meta->rateLimitRemaining);
        $this->assertNull($meta->rateLimitReset);
        $this->assertNull($meta->deprecation);
        $this->assertNull($meta->sunset);
        $this->assertNull($meta->requestId);
    }

    public function testParsesDeprecationAsUnixTimestamp(): void
    {
        // RFC 8594 §2 allows `@<unix-seconds>`
        $response = new Response(200, ['Deprecation' => '@1725148800']);
        $meta = ResponseMetadata::fromResponse($response, 'GET', '/v1/old');

        $this->assertNotNull($meta->deprecation);
        $this->assertSame(1725148800, $meta->deprecation->getTimestamp());
        $this->assertTrue($meta->isDeprecated());
    }

    public function testParsesSunsetAsHttpDate(): void
    {
        // RFC 8594 §3 allows IMF-fixdate (HTTP-date per RFC 7231).
        // Sep 1, 2026 is a Tuesday.
        $response = new Response(200, ['Sunset' => 'Tue, 01 Sep 2026 00:00:00 GMT']);
        $meta = ResponseMetadata::fromResponse($response, 'POST', '/admin/tenants/42/mode');

        $this->assertNotNull($meta->sunset);
        $this->assertSame(1788220800, $meta->sunset->getTimestamp());
    }

    public function testUnparseableDeprecationYieldsNull(): void
    {
        $response = new Response(200, ['Deprecation' => 'not-a-date-at-all']);
        $meta = ResponseMetadata::fromResponse($response, 'GET', '/v1/x');

        $this->assertNull($meta->deprecation);
        $this->assertFalse($meta->isDeprecated());
    }

    public function testRequestIdFallsBackBetweenHeaderNames(): void
    {
        $r1 = new Response(200, ['X-Request-Id' => 'req_abc']);
        $m1 = ResponseMetadata::fromResponse($r1, 'GET', '/v1/x');
        $this->assertSame('req_abc', $m1->requestId);

        $r2 = new Response(200, ['X-SignDocs-Request-Id' => 'req_xyz']);
        $m2 = ResponseMetadata::fromResponse($r2, 'GET', '/v1/x');
        $this->assertSame('req_xyz', $m2->requestId);
    }

    public function testMethodIsNormalizedToUppercase(): void
    {
        $response = new Response(200);
        $meta = ResponseMetadata::fromResponse($response, 'post', '/v1/x');

        $this->assertSame('POST', $meta->method);
    }
}
