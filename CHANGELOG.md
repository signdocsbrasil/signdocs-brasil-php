# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
