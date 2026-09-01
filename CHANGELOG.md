# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.14.0] - 2026-09-01

### Added

- **Capture metrics on every enrollment response**, not just on a dry run.
  `quality` (brightness, sharpness), `pose`, `faceCoverage` and `warnings` now
  come back from a **successful** enrolment too.
  - This closes the gap that mattered: `faceConfidence` answers *"is this a
    face?"*, and a dark, blurred photo scores **99.99** on it. Anyone enrolling
    one user at a time got that reassuring number and no other signal, while
    batch callers using `dryRun` saw the whole picture.
  - The photo is stored either way; knowing it is weak now beats finding out
    from a failed signature three months later. Costs no extra Rekognition
    call — the data was already being fetched and discarded.
- **`dryRun` on the single enrollment endpoint**, for symmetry with the batch,
  plus an `inspect` helper that sets it for you. Same verdict, from the same
  code, so a photo cannot be judged differently depending on which endpoint you
  asked.

## [1.13.0] - 2026-09-01

### Added

- **Batch enrollment** — `POST /v1/users/enrollments`, up to 25 users per
  request. No SDK exposed this endpoint before; the route itself only went live
  the day prior.
  - The documented cap is 25 rows, but the binding limit is the request body
    (~6 MB, and base64 inflates each photo by a third). At 640x640 all 25 slots
    fit; at full camera resolution you get about 8.
  - **Partial success returns `200`.** One unusable photo must not reject the
    other twenty-four, so every row reports its own outcome. Read `results`, not
    the HTTP status, or a half-failed batch looks like a success.
- **`dryRun` — reference photo screening.** Inspects every row and writes
  nothing: no image reaches storage, no record is created, and the 90-day
  retention clock never starts.
  - It exists because Rekognition's confidence answers *"is this a face?"*, not
    *"is this a good reference?"* Measured: a photo at brightness 15 and
    sharpness 13 enrols successfully at 99.99 confidence, then fails face
    matching months later, one user at a time.
  - Three states, since "can I enrol this?" has three answers. **`marginal` is
    the one to act on** — it enrols without complaint today and is exactly what
    becomes a rejected signature later.
  - Rows carry `quality` (brightness, sharpness), `pose` (yaw, pitch, roll),
    `faceCoverage` and `warnings`: `LOW_BRIGHTNESS`, `LOW_SHARPNESS`,
    `FACE_TOO_SMALL`, `HEAD_TURNED`.
  - Costs the same one Rekognition call per row that enrolling costs. The
    saving is not money — it is not storing biometrics already judged unusable.

## [1.12.0] - 2026-08-31

### Added

- **Enrollment read and erase** — `GET` and `DELETE` on
  `/v1/users/{userExternalId}/enrollment`. Only `PUT` was exposed before, so
  neither the re-enrolment sweep nor LGPD art. 18 erasure was reachable from
  the SDK.
  - `GET` reports `expiresAt` / `expired`. The reference image is hard-deleted
    by lifecycle 90 days after enrolment and the record outlives it by a grace
    window *so that* this flag can be found in time. Sweep inside that window:
    once it closes the record goes too and the route answers `404`, which is
    indistinguishable from "never enrolled". Miss it and the expiry surfaces as
    a `422` in the middle of a signature.
  - `DELETE` destroys every stored version of the reference image, not just the
    current one — `versionsDeleted` reports how many.
- **`ENROLLMENT.EXPIRING` / `ENROLLMENT.EXPIRED`** webhook event types.
- **Per-request biometric thresholds** — `policy.minSimilarity` and
  `policy.minLivenessConfidence` let a transaction demand more confidence than
  the account default. They only tighten: a value below the tenant minimum is
  rejected with `400` naming the current floor rather than silently ignored.
  Percentages (`95`) and fractions (`0.95`) both pass through untouched.
- **Advance surface brought in line with the API** — the `confirm_signer` and
  `complete_document_photo` actions, plus `cpfCnpj`, `documentImage`,
  `documentType`, `deviceInfo` and the four sandbox scores
  (`sandboxSimilarity`, `sandboxLivenessConfidence`, `sandboxBrightness`,
  `sandboxSharpness`). The document-photo fallback flow was previously not
  reachable from any SDK.
- **`errorCode` / `errorDetail` / `retryable` / `fallback` on the advance
  response.** This is the one to read if you integrate biometrics: a rejected
  step returns **200** with the session still `ACTIVE` and the reason in the
  body, not as an HTTP error. Code that only branches on the status — or only
  catches exceptions — reads a rejection as a success. Emitted today:
  `BIOMETRIC_MATCH_FAILED`, `LIVENESS_NOT_COMPLETED`, `DOCUMENT_QUALITY_LOW`,
  `DOCUMENT_MATCH_FAILED` and the `SERPRO_*` family.
- **`referenceImage` on session creation** — a per-transaction reference face,
  which allows signing without a prior enrolment.

## [1.11.0] - 2026-08-20

### Added

- **`$client->signingSessions->link($sessionId)`** — `POST /v1/signing-sessions/{sessionId}/link`. The endpoint has
  been in the API and documented in the OpenAPI spec all along, but no SDK in any
  language exposed it, so there was no supported way to recover a signing link
  once the create response was gone.
  - A signing link is single-use: after the signer finishes — or the embed token
    is otherwise consumed — reopening the same URL returns
    `401 Embed token has been consumed`. This mints a new one **without creating
    another transaction and without consuming quota**.
  - Works for standalone and envelope sessions alike.
  - The session must be `ACTIVE`. A completed or cancelled one returns 409: a
    link to a finished session would authenticate nothing. Reach the signed
    document through the envelope's combined stamp or the transaction download
    instead.
  - `expiresAt` is inherited from the original session and is **not** extended.
  - Sends no idempotency key, deliberately. A retry must mint a fresh URL, not
    replay one that has already been consumed.
  - **Authorises the tenant, not the end user.** The API cannot tell which of
    your users is entitled to a given link, so an application whose users share
    one tenant has to establish that itself before calling — otherwise this is a
    way for one user to obtain another's signing credential.
- `MintSigningLinkResponse` model (`sessionId`, `transactionId`, `url`, `expiresAt`, `expiresIn`).

### Fixed

- **The `User-Agent` reported a version nobody was running.** 1.10.0 shipped
  earlier today announcing itself as `signdocs-brasil-php/1.9.0`, because the
  constant and `composer.json` are two independent sources and nothing compared
  them. The constant now moves with the package, and a test asserts they match
  so a release that forgets it fails instead of shipping.

## [1.10.0] - 2026-08-20

### Fixed

- **`addSession`/`verifyDocument` sent no idempotency key** while the client
  retries 429/500/503, so a 500 on an add-session became a second signer, a
  second quota charge and a second invitation, and a retried `verifyDocument`
  paid the metered verification quota twice for an identical result. Pass a
  distinct key per signer: the API scopes its cache by key and resolved path,
  and all signers on an envelope share that path.

## [1.9.0] - 2026-07-30

### Added

- **`$client->envelopes->cancel($envelopeId, $reason)`** — `POST /v1/envelopes/{envelopeId}/cancel` has existed since envelopes shipped and is what the Telegram bot uses, but the SDK never exposed it, leaving PHP consumers to cancel each member session by hand. That workaround does not do the same thing: it leaves the envelope's own status ACTIVE, spends one call per signer, and records N separate events instead of one auditable cancellation.
  - Transitions every non-terminal session and its transaction to CANCELLED, then marks the envelope CANCELLED.
  - Signatures already collected are preserved and reported as `preservedSignedCount` — cancelling stops the pending signers, it never invalidates evidence already gathered.
  - Idempotent: re-cancelling returns 200 with `cancelledCount` 0 and `alreadyCancelled` true.
  - Optional `reason` is recorded in the audit trail; the API defaults it to `envelope_cancelled`.
- `CancelEnvelopeResponse` model (`envelopeId`, `status`, `cancelledCount`, `preservedSignedCount`, `cancelledSessions[]`, `alreadyCancelled`).

### Changed

- `User-Agent` bumped to `signdocs-brasil-php/1.9.0`.

## [1.8.0] - 2026-07-29

### Added

- **`signatureUrl` and `documentFormat` on `DownloadResponse`.** `GET /v1/transactions/{id}/download` has always returned these for non-PDF transactions, but the model parsed only `originalUrl` / `signedUrl` and silently dropped them — so there was no way to reach a detached CAdES signature through the SDK at all. Verified against HML: the API returns six fields where the model exposed four.
  - `documentFormat` is `'pdf'` or `'generic'`, derived by the API from the uploaded bytes (not the filename).
  - `signatureUrl` is the presigned URL for the detached `.p7s`, returned **instead of** `signedUrl` when `documentFormat` is `'generic'` — a non-PDF cannot carry an embedded signature.
  - Caveat worth knowing when consuming it: the API presigns that S3 key without checking that the object exists, so a non-PDF signed under a click/OTP policy still comes back with a `signatureUrl` — one that 404s on GET, because only the digital-certificate step writes a `.p7s`. Branch on the signing policy, not on the field being set.

### Changed

- `User-Agent` bumped to `signdocs-brasil-php/1.8.0`.

## [1.7.1] - 2026-06-25

### Changed

- API-documentation link in README/composer support now points to https://docs.signdocs.com.br (was a dead relative path).

## [1.7.0] - 2026-06-25

### Added

- `$client->verification->verifyDocument(...)` — new method for the `POST /v1/verify/document` endpoint. Inspects a base64-encoded PDF for embedded signatures and returns a `VerifyDocumentResponse` (`signed`, `signatureCount`, `signatures[]`, `checkedAt`). Unlike the other `verification` methods, this endpoint is **authenticated** (sends a Bearer JWT) and requires the `verification:write` scope. It is **production-credentials-only** at runtime — the SDK does not enforce that, but HML credentials are rejected by the API.
- `VerifyDocumentRequest` model (`content`, optional `filename`) — mirrors `UploadDocumentRequest`. `verifyDocument()` also accepts a raw associative array body.
- `VerifyDocumentResponse` model wrapping a list of `DetectedSignature` value objects.
- `DetectedSignature` model (`method`, `type`, `confidence`, optional `subFilter` / `filter`), where `type` is one of `pades`, `pkcs7`, `legacy`, `digital_certificate`.

### Changed

- `User-Agent` bumped to `signdocs-brasil-php/1.7.0`.

## [1.6.0] - 2026-06-19

### Added

- `?bool $otpChannelSelectable` constructor parameter on `Signer` (also surfaced via `toArray()` / `fromArray()`) — lets a signer choose their OTP delivery channel at create-session time.
- `?string $otpChannel` constructor parameter on `AdvanceSessionRequest` (emitted via `toArray()` when set) — selects the OTP delivery channel when advancing a session.
- New `ResendOtpRequest` model with a `?string $channel` property and `toArray()`.
- `SigningSessionsResource::resendOtp()` now accepts an optional `ResendOtpRequest $request` and POSTs the JSON body when a channel is provided (backward compatible — channel is optional).
- The `signer` payload on `SigningSessionBootstrap` is a raw associative array, so `availableOtpChannels` / `otpChannelSelectable` already pass through unchanged.

### Changed

- `User-Agent` bumped to `signdocs-brasil-php/1.6.0`.

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
