<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Errors;

/**
 * Thrown when a request times out or max retry duration is exceeded.
 */
class TimeoutException extends SignDocsBrasilException
{
}
