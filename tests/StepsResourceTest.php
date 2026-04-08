<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Tests;

use PHPUnit\Framework\TestCase;
use SignDocsBrasil\Api\HttpClient;
use SignDocsBrasil\Api\Models\StartStepRequest;
use SignDocsBrasil\Api\Models\StartStepResponse;
use SignDocsBrasil\Api\Models\Step;
use SignDocsBrasil\Api\Models\StepCompleteResponse;
use SignDocsBrasil\Api\Models\StepListResponse;
use SignDocsBrasil\Api\Resources\StepsResource;

final class StepsResourceTest extends TestCase
{
    private function mockHttp(): HttpClient
    {
        return $this->createMock(HttpClient::class);
    }

    public function testListReturnsStepArray(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/transactions/tx_1/steps')
            ->willReturn([
                ['tenantId' => 't1', 'transactionId' => 'tx_1', 'stepId' => 's1', 'type' => 'CLICK', 'status' => 'PENDING', 'order' => 0, 'attempts' => 0, 'maxAttempts' => 3],
                ['tenantId' => 't1', 'transactionId' => 'tx_1', 'stepId' => 's2', 'type' => 'LIVENESS', 'status' => 'PENDING', 'order' => 1, 'attempts' => 0, 'maxAttempts' => 3],
            ]);

        $steps = new StepsResource($http);
        $result = $steps->list('tx_1');

        $this->assertInstanceOf(StepListResponse::class, $result);
        $this->assertCount(2, $result->steps);
        $this->assertInstanceOf(Step::class, $result->steps[0]);
        $this->assertSame('s1', $result->steps[0]->stepId);
        $this->assertSame('CLICK', $result->steps[0]->type);
        $this->assertInstanceOf(Step::class, $result->steps[1]);
        $this->assertSame('s2', $result->steps[1]->stepId);
    }

    public function testListHandlesWrappedResponse(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with('GET', '/v1/transactions/tx_1/steps')
            ->willReturn([
                'steps' => [
                    ['tenantId' => 't1', 'transactionId' => 'tx_1', 'stepId' => 's1', 'type' => 'OTP', 'status' => 'PENDING', 'order' => 0, 'attempts' => 0, 'maxAttempts' => 5],
                ],
            ]);

        $steps = new StepsResource($http);
        $result = $steps->list('tx_1');

        $this->assertInstanceOf(StepListResponse::class, $result);
        $this->assertCount(1, $result->steps);
        $this->assertSame('OTP', $result->steps[0]->type);
    }

    public function testListReturnsEmptyForNullResponse(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->willReturn(null);

        $steps = new StepsResource($http);
        $result = $steps->list('tx_1');

        $this->assertInstanceOf(StepListResponse::class, $result);
        $this->assertSame([], $result->steps);
    }

    public function testStartWithoutBody(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/v1/transactions/tx_1/steps/step_1/start',
                [],
            )
            ->willReturn([
                'stepId' => 'step_1',
                'type' => 'CLICK',
                'status' => 'IN_PROGRESS',
            ]);

        $steps = new StepsResource($http);
        $result = $steps->start('tx_1', 'step_1');

        $this->assertInstanceOf(StartStepResponse::class, $result);
        $this->assertSame('step_1', $result->stepId);
        $this->assertSame('IN_PROGRESS', $result->status);
    }

    public function testStartWithCaptureMode(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/v1/transactions/tx_1/steps/step_2/start',
                ['captureMode' => 'HOSTED_PAGE'],
            )
            ->willReturn([
                'stepId' => 'step_2',
                'type' => 'LIVENESS',
                'status' => 'IN_PROGRESS',
                'hostedUrl' => 'https://capture.signdocs.com.br/abc',
            ]);

        $steps = new StepsResource($http);
        $result = $steps->start(
            'tx_1',
            'step_2',
            new StartStepRequest(captureMode: 'HOSTED_PAGE'),
        );

        $this->assertSame('https://capture.signdocs.com.br/abc', $result->hostedUrl);
    }

    public function testCompleteWithBody(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/v1/transactions/tx_1/steps/step_3/complete',
                ['otpCode' => '123456'],
            )
            ->willReturn([
                'stepId' => 'step_3',
                'type' => 'OTP',
                'status' => 'COMPLETED',
                'attempts' => 1,
                'result' => ['otp' => ['verified' => true]],
            ]);

        $steps = new StepsResource($http);
        $result = $steps->complete('tx_1', 'step_3', ['otpCode' => '123456']);

        $this->assertInstanceOf(StepCompleteResponse::class, $result);
        $this->assertSame('COMPLETED', $result->status);
        $this->assertNotEmpty($result->result);
    }

    public function testCompleteWithoutBody(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                '/v1/transactions/tx_1/steps/step_click/complete',
                [],
            )
            ->willReturn([
                'stepId' => 'step_click',
                'type' => 'CLICK',
                'status' => 'COMPLETED',
                'attempts' => 1,
            ]);

        $steps = new StepsResource($http);
        $result = $steps->complete('tx_1', 'step_click');

        $this->assertInstanceOf(StepCompleteResponse::class, $result);
        $this->assertSame('CLICK', $result->type);
        $this->assertSame('COMPLETED', $result->status);
    }

    public function testStartOtpReturnsCode(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->willReturn([
                'stepId' => 'step_otp',
                'type' => 'OTP',
                'status' => 'IN_PROGRESS',
                'otpCode' => '654321',
                'message' => 'Code sent via SMS',
            ]);

        $steps = new StepsResource($http);
        $result = $steps->start('tx_1', 'step_otp');

        $this->assertSame('654321', $result->otpCode);
        $this->assertSame('Code sent via SMS', $result->message);
    }

    public function testStartLivenessReturnsSessionId(): void
    {
        $http = $this->mockHttp();
        $http->expects($this->once())
            ->method('request')
            ->willReturn([
                'stepId' => 'step_live',
                'type' => 'LIVENESS',
                'status' => 'IN_PROGRESS',
                'livenessSessionId' => 'session_xyz',
            ]);

        $steps = new StepsResource($http);
        $result = $steps->start('tx_1', 'step_live');

        $this->assertSame('session_xyz', $result->livenessSessionId);
    }
}
