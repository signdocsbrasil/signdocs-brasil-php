<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Errors;

/**
 * HTTP 429 Too Many Requests.
 */
class RateLimitException extends ApiException
{
    public function __construct(
        ProblemDetail $problemDetail,
        public readonly ?int $retryAfterSeconds = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($problemDetail, $previous);
    }
}
