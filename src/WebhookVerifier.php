<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api;

/**
 * Verifies incoming webhook signatures using HMAC-SHA256.
 *
 * Usage:
 *   $isValid = WebhookVerifier::verify(
 *       body: $rawBody,
 *       signatureHeader: $_SERVER['HTTP_X_SIGNDOCS_SIGNATURE'],
 *       timestampHeader: $_SERVER['HTTP_X_SIGNDOCS_TIMESTAMP'],
 *       secret: $webhookSecret,
 *   );
 */
final class WebhookVerifier
{
    /** Default tolerance for timestamp drift in seconds. */
    private const DEFAULT_TOLERANCE_SECONDS = 300;

    /**
     * Verify a webhook signature.
     *
     * @param string $body            Raw request body string
     * @param string $signatureHeader Value of the X-SignDocs-Signature header (hex-encoded HMAC)
     * @param string $timestampHeader Value of the X-SignDocs-Timestamp header (Unix epoch seconds)
     * @param string $secret          Webhook signing secret (returned at registration time)
     * @param int    $toleranceSeconds Maximum allowed age of the webhook in seconds (default 300)
     * @return bool True if the signature is valid and within tolerance
     */
    public static function verify(
        string $body,
        string $signatureHeader,
        string $timestampHeader,
        string $secret,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS,
    ): bool {
        // Parse and validate the timestamp
        if (!is_numeric($timestampHeader)) {
            return false;
        }

        $timestamp = (int) $timestampHeader;
        $now = time();

        if (abs($now - $timestamp) > $toleranceSeconds) {
            return false;
        }

        // Compute the expected signature
        $signingInput = "{$timestamp}.{$body}";
        $expected = hash_hmac('sha256', $signingInput, $secret);

        // Constant-time comparison to prevent timing attacks
        return hash_equals($expected, $signatureHeader);
    }
}
