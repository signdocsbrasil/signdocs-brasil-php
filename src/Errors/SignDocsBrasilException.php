<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Errors;

/**
 * Base exception for all SignDocsBrasil SDK errors.
 */
class SignDocsBrasilException extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
