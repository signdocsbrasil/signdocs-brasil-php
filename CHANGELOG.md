# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.0] - 2026-04-27

### Added

- `?string $envelopeId` constructor parameter on `VerificationResponse` (also surfaced via `toArray()` / `fromArray()`) — populated when the verified evidence belongs to a multi-signer envelope. Use it with `$client->verification()->verifyEnvelope($envelopeId)` for cross-signer drill-down.
- Three new `WebhookEventType` enum cases:
  - `EnvelopeCreated` (`ENVELOPE.CREATED`)
  - `EnvelopeAllSigned` (`ENVELOPE.ALL_SIGNED`)
  - `EnvelopeExpired` (`ENVELOPE.EXPIRED`)

### Changed

- `User-Agent` bumped to `signdocs-brasil-php/1.5.0`.

## [1.4.1] - 2026-04-27

### Fixed

- **`WebhookTestResponse` shape aligned with the API.** The model declared `{deliveryId, status, statusCode}` but the API actually returns `{webhookId, testDelivery: {httpStatus, success, error?, timestamp}}` per the OpenAPI spec. Calls to `$client->webhooks->test()` were returning all-empty fields against the live HML environment. Added a new `WebhookTestDelivery` value object and rewrote `WebhookTestResponse` to wrap it. Same fix shipped in lockstep across the TypeScript, Python, Go, Java, and .NET SDKs.

### Changed

- `User-Agent` bumped from `signdocs-brasil-php/1.4.0` to `signdocs-brasil-php/1.4.1`.

## [1.4.0] - 2026-04-23

### Fixed (BREAKING IF YOU SOMEHOW USED 1.x SUCCESSFULLY)

- **Realigned every signing-session and envelope model class to match the actual API schema.** Releases 1.0.0 through 1.3.0 shipped with hand-written models that didn't match what the server validates: `CreateSigningSessionRequest` used legacy fields (`name`, `type`, `signers[]`, `documents[]`, `callbackUrl`, `redirectUrl`, `brandingId`) that the API has never accepted, so any call would have returned 400 Bad Request. The TypeScript / Python / Go SDKs already used the correct shape; this brings PHP into alignment.
- Affected classes: `CreateSigningSessionRequest`, `SigningSession`, `SigningSessionStatus`, `CancelSigningSessionResponse`, `CreateEnvelopeRequest`, `Envelope`, `AddEnvelopeSessionRequest`, `EnvelopeSession`, `EnvelopeSessionSummary`, `EnvelopeDetail`. The new shape uses `purpose`, `policy`, `signer`, `document`, `returnUrl`, `cancelUrl`, `metadata`, `locale`, `expiresInMinutes`, `appearance` — matching the OpenAPI spec.

### Added

- `Owner` class — optional requester identity (`?string $email`, `?string $name`) on `CreateSigningSessionRequest` and `CreateEnvelopeRequest`. When provided, SignDocs automatically emails each signer an invitation with their signing URL (when `signer.email` differs from `owner.email`, case-insensitive) and emails the owner a completion notification per signer completion (plus a final "all signed" message for envelopes). Omit to keep the traditional behavior.
- `inviteSent` (`?bool`) on `SigningSession` and `EnvelopeSession` response models. Populated by the API when an invitation email was dispatched.

### Changed

- `User-Agent` bumped to `signdocs-brasil-php/1.4.0`.

## [1.3.0] - 2026-04-20

### Added

- `SignDocsBrasil\Api\TokenCache\TokenCacheInterface` — pluggable OAuth token cache. Inject via `new Config(tokenCache: $myCache)` to share tokens across PHP-FPM / serverless workers. Default `InMemoryTokenCache` preserves pre-1.3 single-process behavior.
- `SignDocsBrasil\Api\TokenCache\CachedToken` value object and `SignDocsBrasil\Api\TokenCache\InMemoryTokenCache` default implementation.
- `SignDocsBrasil\Api\ResponseMetadata` — captures `RateLimit-*`, `Deprecation`, `Sunset`, and request-ID headers from every API response. Register an observer via `new Config(onResponse: fn(ResponseMetadata $m) => ...)`.
- `SignDocsBrasil\Api\WebhookEventType` — PHP 8.1 string-backed enum with all 17 canonical event types, matching the OpenAPI spec `WebhookEventType`. Includes NT65 `isNt65()` predicate.
- Webhook event types for the NT65 INSS consignado flow:
  - `STEP.PURPOSE_DISCLOSURE_SENT` — purpose-disclosure notification delivered to the beneficiary
  - `TRANSACTION.DEADLINE_APPROACHING` — ≤2 business days remaining until the INSS submission deadline

### Changed

- `SignDocsBrasil\Api\AuthHandler` is no longer `final`. Subclassing is supported; prefer injecting a `TokenCacheInterface` over subclassing for most use cases.
- `AuthHandler::getAccessToken()` now reads from and writes to the configured `TokenCacheInterface`. Cache keys are derived deterministically from `clientId + baseUrl + scopes` (SHA-256 truncated to 32 chars) so the same credentials reuse the same cached token across process boundaries.
- `AuthHandler::invalidate()` now deletes the cache entry instead of clearing an internal field.
- `SDK_VERSION` bumped to `1.3.0` (sent as `User-Agent`).

### Deprecated

- None.

### Fixed

- None.

## [1.2.0] - 2026-04-14

### Added

- `$client->verification->verifyEnvelope($envelopeId)` — public method for the new `GET /v1/verify/envelope/{envelopeId}` endpoint. Returns envelope status, signers list (each with `evidenceId` for drill-down via `verification->verify()`), and consolidated download URLs.
- `EnvelopeVerificationResponse` model. For non-PDF envelopes signed with digital certificates, `$response->downloads['consolidatedSignature']` exposes a single PKCS#7 / CMS detached `.p7s` containing every signer's `SignerInfo`. For PDF envelopes, `$response->downloads['combinedSignedPdf']` exposes the merged PDF.
- `VerificationResponse->tenantCnpj` field and `signer['cpfCnpj']` key (previously returned by the API but not modeled by the SDK).
- `VerificationDownloadsResponse->downloads['originalDocument']` and `['signedSignature']` keys (previously undocumented), matching the real shape the API returns.

### Changed

- `VerificationDownloadsResponse->downloads['signedSignature']` is now absent when the evidence belongs to a multi-signer envelope (the API omits the field). For standalone signing sessions (single-signer non-PDF with digital certificate) the field is still populated. To retrieve the consolidated `.p7s` for an envelope, use `$client->verification->verifyEnvelope()` instead.

### Removed

- `VerificationDownloadsResponse->downloads['signedPdf']` — the field was documented by the SDK but never actually returned by the API. No real-world consumer could have depended on it.

## [1.1.0] - 2026-03-27

### Added

- Envelopes resource (`$client->envelopes`): create, get, addSession, combinedStamp — multi-signer workflows with parallel or sequential signing
- New models: CreateEnvelopeRequest, Envelope, AddEnvelopeSessionRequest, EnvelopeSession, EnvelopeSessionSummary, EnvelopeDetail, EnvelopeCombinedStampResponse

## [1.0.0] - 2026-03-02

### Added

- Full API coverage: transactions, documents, steps, signing, evidence, verification, users, webhooks, documentGroups, health
- OAuth2 `client_credentials` authentication with client secret
- Private Key JWT (ES256) authentication with `client_assertion`
- Automatic token caching with 30-second refresh buffer
- Auto-pagination via `listAutoPaginate()` returning PHP Generator
- Exponential backoff retry with jitter (429, 500, 503)
- Retry-After header support
- Idempotency keys (auto-generated UUID) on POST requests
- Typed exceptions for all HTTP error codes (RFC 7807 ProblemDetail)
- Webhook signature verification (HMAC-SHA256, constant-time comparison)
- Configurable base URL, timeout, max retries, and scopes
- `declare(strict_types=1)` on all files
- Immutable model objects with readonly properties
- PSR-4 autoloading
- PHP 8.1+ support
