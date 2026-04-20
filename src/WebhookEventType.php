<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api;

/**
 * Canonical set of webhook event types accepted by the SignDocs API.
 *
 * Stays in lockstep with the OpenAPI spec `WebhookEventType` enum at
 * `openapi/openapi.yaml`. Events tagged NT65 are emitted only for
 * tenants with `nt65ComplianceEnabled` (INSS consignado workflow).
 */
enum WebhookEventType: string
{
    case TransactionCreated = 'TRANSACTION.CREATED';
    case TransactionCompleted = 'TRANSACTION.COMPLETED';
    case TransactionCancelled = 'TRANSACTION.CANCELLED';
    case TransactionFailed = 'TRANSACTION.FAILED';
    case TransactionExpired = 'TRANSACTION.EXPIRED';
    case TransactionFallback = 'TRANSACTION.FALLBACK';
    case TransactionDeadlineApproaching = 'TRANSACTION.DEADLINE_APPROACHING';

    case StepStarted = 'STEP.STARTED';
    case StepCompleted = 'STEP.COMPLETED';
    case StepFailed = 'STEP.FAILED';
    case StepPurposeDisclosureSent = 'STEP.PURPOSE_DISCLOSURE_SENT';

    case QuotaWarning = 'QUOTA.WARNING';
    case ApiDeprecationNotice = 'API.DEPRECATION_NOTICE';

    case SigningSessionCreated = 'SIGNING_SESSION.CREATED';
    case SigningSessionCompleted = 'SIGNING_SESSION.COMPLETED';
    case SigningSessionCancelled = 'SIGNING_SESSION.CANCELLED';
    case SigningSessionExpired = 'SIGNING_SESSION.EXPIRED';

    /**
     * True if this event is part of the NT65 INSS consignado flow and
     * only emitted for tenants with `nt65ComplianceEnabled`.
     */
    public function isNt65(): bool
    {
        return match ($this) {
            self::TransactionDeadlineApproaching,
            self::StepPurposeDisclosureSent => true,
            default => false,
        };
    }
}
