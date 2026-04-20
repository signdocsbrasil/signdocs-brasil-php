<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Tests;

use PHPUnit\Framework\TestCase;
use SignDocsBrasil\Api\WebhookEventType;

final class WebhookEventTypeTest extends TestCase
{
    /**
     * Lockstep with the OpenAPI spec `WebhookEventType` enum. If this
     * array diverges from `openapi/openapi.yaml`, one side is wrong.
     */
    private const SPEC_EVENTS = [
        'TRANSACTION.CREATED',
        'TRANSACTION.COMPLETED',
        'TRANSACTION.CANCELLED',
        'TRANSACTION.FAILED',
        'TRANSACTION.EXPIRED',
        'TRANSACTION.FALLBACK',
        'TRANSACTION.DEADLINE_APPROACHING',
        'STEP.STARTED',
        'STEP.COMPLETED',
        'STEP.FAILED',
        'STEP.PURPOSE_DISCLOSURE_SENT',
        'QUOTA.WARNING',
        'API.DEPRECATION_NOTICE',
        'SIGNING_SESSION.CREATED',
        'SIGNING_SESSION.COMPLETED',
        'SIGNING_SESSION.CANCELLED',
        'SIGNING_SESSION.EXPIRED',
    ];

    public function testAllSpecEventsExist(): void
    {
        $implemented = array_map(
            static fn(WebhookEventType $c) => $c->value,
            WebhookEventType::cases(),
        );

        foreach (self::SPEC_EVENTS as $eventName) {
            $this->assertContains(
                $eventName,
                $implemented,
                "Spec event '{$eventName}' is missing from WebhookEventType enum"
            );
        }
    }

    public function testNoExtraEventsBeyondSpec(): void
    {
        $implemented = array_map(
            static fn(WebhookEventType $c) => $c->value,
            WebhookEventType::cases(),
        );

        foreach ($implemented as $eventName) {
            $this->assertContains(
                $eventName,
                self::SPEC_EVENTS,
                "Enum event '{$eventName}' is not in the OpenAPI spec"
            );
        }
    }

    public function testCountMatchesSpec(): void
    {
        $this->assertCount(
            count(self::SPEC_EVENTS),
            WebhookEventType::cases(),
        );
    }

    public function testNt65EventsAreFlagged(): void
    {
        $this->assertTrue(WebhookEventType::TransactionDeadlineApproaching->isNt65());
        $this->assertTrue(WebhookEventType::StepPurposeDisclosureSent->isNt65());

        $this->assertFalse(WebhookEventType::TransactionCompleted->isNt65());
        $this->assertFalse(WebhookEventType::StepCompleted->isNt65());
        $this->assertFalse(WebhookEventType::QuotaWarning->isNt65());
    }

    public function testFromValueRoundTrip(): void
    {
        $this->assertSame(
            WebhookEventType::StepPurposeDisclosureSent,
            WebhookEventType::from('STEP.PURPOSE_DISCLOSURE_SENT'),
        );
        $this->assertSame(
            WebhookEventType::TransactionDeadlineApproaching,
            WebhookEventType::from('TRANSACTION.DEADLINE_APPROACHING'),
        );
    }
}
