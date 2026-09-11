<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class AddEnvelopeSessionRequest
{
    /**
     * @param Signer                     $signer      Signer information (name, userExternalId, cpf/cnpj, email, phone, otpChannel, birthDate).
     * @param Policy                     $policy      Verification policy (profile + optional customSteps).
     * @param int                        $signerIndex Index of the signer in the envelope (minimum 1; order for SEQUENTIAL mode).
     * @param string|null                $purpose     Session purpose (DOCUMENT_SIGNATURE or ACTION_AUTHENTICATION). Defaults server-side to DOCUMENT_SIGNATURE.
     * @param string|null                $returnUrl   Return URL after completion of this session (overrides envelope-level URL).
     * @param string|null                $cancelUrl   Cancel URL (overrides envelope-level URL).
     * @param array<string, string>|null $metadata    Session-specific metadata.
     * @param list<string>|null          $deliverVia  Channels that deliver the signing link to this signer: email, whatsapp and/or telegram. Null keeps the previous behavior (the invite email only). In a SEQUENTIAL envelope a later signer receives the link over these channels when their turn comes. Same rules as {@see CreateSigningSessionRequest::$deliverVia}.
     */
    public function __construct(
        public readonly Signer $signer,
        public readonly Policy $policy,
        public readonly int $signerIndex,
        public readonly ?string $purpose = null,
        public readonly ?string $returnUrl = null,
        public readonly ?string $cancelUrl = null,
        public readonly ?array $metadata = null,
        public readonly ?array $deliverVia = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            signer: Signer::fromArray($data['signer'] ?? []),
            policy: Policy::fromArray($data['policy'] ?? []),
            signerIndex: (int) ($data['signerIndex'] ?? 1),
            purpose: isset($data['purpose']) ? (string) $data['purpose'] : null,
            returnUrl: isset($data['returnUrl']) ? (string) $data['returnUrl'] : null,
            cancelUrl: isset($data['cancelUrl']) ? (string) $data['cancelUrl'] : null,
            metadata: $data['metadata'] ?? null,
            deliverVia: $data['deliverVia'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'signer' => $this->signer->toArray(),
            'policy' => $this->policy->toArray(),
            'signerIndex' => $this->signerIndex,
        ];

        if ($this->purpose !== null) {
            $result['purpose'] = $this->purpose;
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
        if ($this->deliverVia !== null) {
            $result['deliverVia'] = $this->deliverVia;
        }

        return $result;
    }
}
