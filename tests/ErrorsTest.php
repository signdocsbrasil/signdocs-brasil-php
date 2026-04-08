<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SignDocsBrasil\Api\Errors\ApiException;
use SignDocsBrasil\Api\Errors\BadRequestException;
use SignDocsBrasil\Api\Errors\ConflictException;
use SignDocsBrasil\Api\Errors\ForbiddenException;
use SignDocsBrasil\Api\Errors\InternalServerException;
use SignDocsBrasil\Api\Errors\NotFoundException;
use SignDocsBrasil\Api\Errors\ProblemDetail;
use SignDocsBrasil\Api\Errors\RateLimitException;
use SignDocsBrasil\Api\Errors\ServiceUnavailableException;
use SignDocsBrasil\Api\Errors\UnauthorizedException;
use SignDocsBrasil\Api\Errors\UnprocessableEntityException;

final class ErrorsTest extends TestCase
{
    public function testProblemDetailFromArray(): void
    {
        $pd = ProblemDetail::fromArray([
            'type' => 'about:blank',
            'title' => 'Bad Request',
            'status' => 400,
            'detail' => 'Invalid input',
        ]);

        $this->assertSame('about:blank', $pd->type);
        $this->assertSame('Bad Request', $pd->title);
        $this->assertSame(400, $pd->status);
        $this->assertSame('Invalid input', $pd->detail);
    }

    public function testProblemDetailExtensions(): void
    {
        $pd = ProblemDetail::fromArray([
            'type' => 'about:blank',
            'title' => 'Error',
            'status' => 400,
            'custom_field' => 'custom_value',
            'errors' => [['field' => 'name']],
        ]);

        $this->assertSame('custom_value', $pd->extensions['custom_field']);
        $this->assertCount(1, $pd->extensions['errors']);
    }

    public function testProblemDetailToArray(): void
    {
        $pd = new ProblemDetail(
            type: 'about:blank',
            title: 'Bad Request',
            status: 400,
            detail: 'Invalid',
        );
        $arr = $pd->toArray();

        $this->assertSame('about:blank', $arr['type']);
        $this->assertSame(400, $arr['status']);
        $this->assertSame('Invalid', $arr['detail']);
    }

    public static function statusCodeProvider(): array
    {
        return [
            [400, BadRequestException::class],
            [401, UnauthorizedException::class],
            [403, ForbiddenException::class],
            [404, NotFoundException::class],
            [409, ConflictException::class],
            [422, UnprocessableEntityException::class],
            [429, RateLimitException::class],
            [500, InternalServerException::class],
            [503, ServiceUnavailableException::class],
        ];
    }

    #[DataProvider('statusCodeProvider')]
    public function testFromResponseStatusMapping(int $status, string $expectedClass): void
    {
        $body = ['type' => 'about:blank', 'title' => "HTTP {$status}", 'status' => $status];
        $error = ApiException::fromResponse($status, $body);

        $this->assertInstanceOf($expectedClass, $error);
        $this->assertSame($status, $error->getStatus());
    }

    public function testUnknownStatusReturnsBaseApiException(): void
    {
        $error = ApiException::fromResponse(418, ['type' => 'about:blank', 'title' => 'Teapot', 'status' => 418]);
        $this->assertInstanceOf(ApiException::class, $error);
        $this->assertNotInstanceOf(BadRequestException::class, $error);
        $this->assertSame(418, $error->getStatus());
    }

    public function testRateLimitRetryAfter(): void
    {
        $error = ApiException::fromResponse(429, ['type' => 'about:blank', 'title' => 'Rate Limited', 'status' => 429], 5);

        $this->assertInstanceOf(RateLimitException::class, $error);
        $this->assertSame(5, $error->retryAfterSeconds);
    }

    public function testFallbackProblemDetail(): void
    {
        $error = ApiException::fromResponse(500, ['message' => 'Server Error']);
        $this->assertInstanceOf(InternalServerException::class, $error);
        $this->assertStringContainsString('errors/500', $error->getType());
    }

    public function testApiExceptionGetters(): void
    {
        $pd = new ProblemDetail(
            type: 'about:blank',
            title: 'Not Found',
            status: 404,
            detail: 'Resource not found',
            instance: '/v1/test',
        );
        $error = new NotFoundException($pd);

        $this->assertSame('about:blank', $error->getType());
        $this->assertSame('Not Found', $error->getTitle());
        $this->assertSame('Resource not found', $error->getDetail());
        $this->assertSame('/v1/test', $error->getInstance());
    }
}
