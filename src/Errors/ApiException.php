<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Errors;

/**
 * Exception for HTTP API errors containing an RFC 7807 ProblemDetail.
 */
class ApiException extends SignDocsBrasilException
{
    public function __construct(
        public readonly ProblemDetail $problemDetail,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            $problemDetail->detail ?? $problemDetail->title,
            $problemDetail->status,
            $previous,
        );
    }

    public function getStatus(): int
    {
        return $this->problemDetail->status;
    }

    public function getType(): string
    {
        return $this->problemDetail->type;
    }

    public function getTitle(): string
    {
        return $this->problemDetail->title;
    }

    public function getDetail(): ?string
    {
        return $this->problemDetail->detail;
    }

    public function getInstance(): ?string
    {
        return $this->problemDetail->instance;
    }

    /**
     * Parse an API error response and return the appropriate exception subclass.
     *
     * @param int                  $status     HTTP status code
     * @param array<string, mixed> $body       Decoded JSON response body
     * @param int|null             $retryAfter Retry-After header value in seconds
     */
    public static function fromResponse(int $status, array $body, ?int $retryAfter = null): self
    {
        $problemDetail = self::buildProblemDetail($status, $body);

        return match ($status) {
            400 => new BadRequestException($problemDetail),
            401 => new UnauthorizedException($problemDetail),
            403 => new ForbiddenException($problemDetail),
            404 => new NotFoundException($problemDetail),
            409 => new ConflictException($problemDetail),
            422 => new UnprocessableEntityException($problemDetail),
            429 => new RateLimitException($problemDetail, $retryAfter),
            500 => new InternalServerException($problemDetail),
            503 => new ServiceUnavailableException($problemDetail),
            default => new self($problemDetail),
        };
    }

    /**
     * @param array<string, mixed> $body
     */
    private static function buildProblemDetail(int $status, array $body): ProblemDetail
    {
        if (isset($body['type'])) {
            return ProblemDetail::fromArray($body);
        }

        return new ProblemDetail(
            type: "https://api.signdocs.com.br/errors/{$status}",
            title: "HTTP {$status}",
            status: $status,
            detail: isset($body['message']) ? (string) $body['message'] : null,
        );
    }
}
