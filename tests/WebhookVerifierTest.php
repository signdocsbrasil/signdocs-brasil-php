<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Tests;

use PHPUnit\Framework\TestCase;
use SignDocsBrasil\Api\WebhookVerifier;

final class WebhookVerifierTest extends TestCase
{
    private function sign(string $body, string $secret, int $timestamp): string
    {
        $signingInput = "{$timestamp}.{$body}";
        return hash_hmac('sha256', $signingInput, $secret);
    }

    public function testValidSignature(): void
    {
        $body = '{"event":"transaction.completed"}';
        $secret = 'whsec_test123';
        $ts = time();
        $sig = $this->sign($body, $secret, $ts);

        $this->assertTrue(WebhookVerifier::verify(
            body: $body,
            signatureHeader: $sig,
            timestampHeader: (string) $ts,
            secret: $secret,
        ));
    }

    public function testInvalidSignature(): void
    {
        $this->assertFalse(WebhookVerifier::verify(
            body: '{}',
            signatureHeader: 'invalid_hex',
            timestampHeader: (string) time(),
            secret: 'secret',
        ));
    }

    public function testExpiredTimestamp(): void
    {
        $body = '{"event":"test"}';
        $secret = 'whsec_test';
        $ts = time() - 400; // > 300s ago
        $sig = $this->sign($body, $secret, $ts);

        $this->assertFalse(WebhookVerifier::verify(
            body: $body,
            signatureHeader: $sig,
            timestampHeader: (string) $ts,
            secret: $secret,
        ));
    }

    public function testFutureTimestamp(): void
    {
        $body = '{"event":"test"}';
        $secret = 'whsec_test';
        $ts = time() + 400; // > 300s in future
        $sig = $this->sign($body, $secret, $ts);

        $this->assertFalse(WebhookVerifier::verify(
            body: $body,
            signatureHeader: $sig,
            timestampHeader: (string) $ts,
            secret: $secret,
        ));
    }

    public function testCustomTolerance(): void
    {
        $body = '{"event":"test"}';
        $secret = 'whsec_test';
        $ts = time() - 100;
        $sig = $this->sign($body, $secret, $ts);

        $this->assertFalse(WebhookVerifier::verify(
            body: $body,
            signatureHeader: $sig,
            timestampHeader: (string) $ts,
            secret: $secret,
            toleranceSeconds: 50,
        ));

        $this->assertTrue(WebhookVerifier::verify(
            body: $body,
            signatureHeader: $sig,
            timestampHeader: (string) $ts,
            secret: $secret,
            toleranceSeconds: 200,
        ));
    }

    public function testWrongSecret(): void
    {
        $body = '{"event":"test"}';
        $ts = time();
        $sig = $this->sign($body, 'correct_secret', $ts);

        $this->assertFalse(WebhookVerifier::verify(
            body: $body,
            signatureHeader: $sig,
            timestampHeader: (string) $ts,
            secret: 'wrong_secret',
        ));
    }

    public function testNonNumericTimestamp(): void
    {
        $this->assertFalse(WebhookVerifier::verify(
            body: '{}',
            signatureHeader: 'abc',
            timestampHeader: 'not-a-number',
            secret: 'secret',
        ));
    }
}
