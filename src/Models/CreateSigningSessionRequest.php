<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class CreateSigningSessionRequest
{
    /**
     * @param string                     $purpose          Transaction purpose (DOCUMENT_SIGNATURE or ACTION_AUTHENTICATION).
     * @param Policy                     $policy           Verification policy (profile + optional customSteps).
     * @param Signer                     $signer           Signer information (name, userExternalId, cpf/cnpj, email, phone, otpChannel, birthDate).
     * @param array<string, mixed>|null  $document         Inline document with 'content' (base64) and optional 'filename'.
     * @param array<string, mixed>|null  $action           Action metadata (type, description, optional reference) for ACTION_AUTHENTICATION sessions.
     * @param string|null                $returnUrl        URL to redirect to after completion.
     * @param string|null                $cancelUrl        URL to redirect to on cancellation.
     * @param array<string, string>|null $metadata         Arbitrary key-value metadata.
     * @param string|null                $locale           Locale for the signing UI (pt-BR, en, es).
     * @param int|null                   $expiresInMinutes Custom expiration time in minutes (5–1440).
     * @param array<string, mixed>|null  $appearance       Branding configuration for the signing page.
     * @param Owner|null                 $owner            Identity of the requester (see {@see Owner}); enables auto-invite emails and completion notifications.
     */
    public function __construct(
        public readonly string $purpose,
        public readonly Policy $policy,
        public readonly Signer $signer,
        public readonly ?array $document = null,
        public readonly ?array $action = null,
        public readonly ?string $returnUrl = null,
        public readonly ?string $cancelUrl = null,
        public readonly ?array $metadata = null,
        public readonly ?string $locale = null,
        public readonly ?int $expiresInMinutes = null,
        public readonly ?array $appearance = null,
        public readonly ?Owner $owner = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            purpose: (string) ($data['purpose'] ?? ''),
            policy: Policy::fromArray($data['policy'] ?? []),
            signer: Signer::fromArray($data['signer'] ?? []),
            document: $data['document'] ?? null,
            action: $data['action'] ?? null,
            returnUrl: isset($data['returnUrl']) ? (string) $data['returnUrl'] : null,
            cancelUrl: isset($data['cancelUrl']) ? (string) $data['cancelUrl'] : null,
            metadata: $data['metadata'] ?? null,
            locale: isset($data['locale']) ? (string) $data['locale'] : null,
            expiresInMinutes: isset($data['expiresInMinutes']) ? (int) $data['expiresInMinutes'] : null,
            appearance: $data['appearance'] ?? null,
            owner: isset($data['owner']) && is_array($data['owner']) ? Owner::fromArray($data['owner']) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'purpose' => $this->purpose,
            'policy' => $this->policy->toArray(),
            'signer' => $this->signer->toArray(),
        ];

        if ($this->document !== null) {
            $result['document'] = $this->document;
        }
        if ($this->action !== null) {
            $result['action'] = $this->action;
        }
        if ($this->returnUrl !== null) {
            $result['returnUrl'] = $this->returnUrl;
        }
        if ($this->cancelUrl !== null) {
            $result['cancelUrl'] = $this->cancelUrl;
        }
        if ($this->metadata !== null) {
            $result['metadata'] = $this->metadata;
        }
        if ($this->locale !== null) {
            $result['locale'] = $this->locale;
        }
        if ($this->expiresInMinutes !== null) {
            $result['expiresInMinutes'] = $this->expiresInMinutes;
        }
        if ($this->appearance !== null) {
            $result['appearance'] = $this->appearance;
        }
        if ($this->owner !== null) {
            $ownerArr = $this->owner->toArray();
            if ($ownerArr !== []) {
                $result['owner'] = $ownerArr;
            }
        }

        return $result;
    }
}
