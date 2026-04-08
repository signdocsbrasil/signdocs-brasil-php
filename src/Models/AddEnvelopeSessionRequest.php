<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class AddEnvelopeSessionRequest
{
    /**
     * @param string                     $signerName        Signer display name
     * @param string                     $signerUserExternalId  External user identifier
     * @param string|null                $signerCpf         Signer CPF
     * @param string|null                $signerCnpj        Signer CNPJ
     * @param string|null                $signerEmail       Signer email address
     * @param string|null                $signerPhone       Signer phone number
     * @param string|null                $signerBirthDate   Signer birth date (YYYY-MM-DD)
     * @param string|null                $signerOtpChannel  OTP delivery channel (SMS, EMAIL)
     * @param string                     $policyProfile     Policy profile for verification
     * @param string                     $purpose           Signing purpose
     * @param int                        $signerIndex       Index of the signer in the envelope
     * @param string|null                $returnUrl         Redirect URL after signing
     * @param string|null                $cancelUrl         Redirect URL on cancel
     * @param array<string, string>|null $metadata          Arbitrary key-value metadata
     */
    public function __construct(
        public readonly string $signerName,
        public readonly string $signerUserExternalId = 'sdk',
        public readonly ?string $signerCpf = null,
        public readonly ?string $signerCnpj = null,
        public readonly ?string $signerEmail = null,
        public readonly ?string $signerPhone = null,
        public readonly ?string $signerBirthDate = null,
        public readonly ?string $signerOtpChannel = null,
        public readonly string $policyProfile = 'CLICK_ONLY',
        public readonly string $purpose = 'DOCUMENT_SIGNATURE',
        public readonly int $signerIndex = 1,
        public readonly ?string $returnUrl = null,
        public readonly ?string $cancelUrl = null,
        public readonly ?array $metadata = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $signer = $data['signer'] ?? [];

        return new self(
            signerName: (string) ($signer['name'] ?? ''),
            signerUserExternalId: (string) ($signer['userExternalId'] ?? 'sdk'),
            signerCpf: isset($signer['cpf']) ? (string) $signer['cpf'] : null,
            signerCnpj: isset($signer['cnpj']) ? (string) $signer['cnpj'] : null,
            signerEmail: isset($signer['email']) ? (string) $signer['email'] : null,
            signerPhone: isset($signer['phone']) ? (string) $signer['phone'] : null,
            signerBirthDate: isset($signer['birthDate']) ? (string) $signer['birthDate'] : null,
            signerOtpChannel: isset($signer['otpChannel']) ? (string) $signer['otpChannel'] : null,
            policyProfile: (string) ($data['policyProfile'] ?? 'CLICK_ONLY'),
            purpose: (string) ($data['purpose'] ?? 'DOCUMENT_SIGNATURE'),
            signerIndex: (int) ($data['signerIndex'] ?? 1),
            returnUrl: isset($data['returnUrl']) ? (string) $data['returnUrl'] : null,
            cancelUrl: isset($data['cancelUrl']) ? (string) $data['cancelUrl'] : null,
            metadata: $data['metadata'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $signer = [
            'name' => $this->signerName,
            'userExternalId' => $this->signerUserExternalId,
        ];

        if ($this->signerCpf !== null) {
            $signer['cpf'] = $this->signerCpf;
        }
        if ($this->signerCnpj !== null) {
            $signer['cnpj'] = $this->signerCnpj;
        }
        if ($this->signerEmail !== null) {
            $signer['email'] = $this->signerEmail;
        }
        if ($this->signerPhone !== null) {
            $signer['phone'] = $this->signerPhone;
        }
        if ($this->signerBirthDate !== null) {
            $signer['birthDate'] = $this->signerBirthDate;
        }
        if ($this->signerOtpChannel !== null) {
            $signer['otpChannel'] = $this->signerOtpChannel;
        }

        $result = [
            'signer' => $signer,
            'policyProfile' => $this->policyProfile,
            'purpose' => $this->purpose,
            'signerIndex' => $this->signerIndex,
        ];

        if ($this->returnUrl !== null) {
            $result['returnUrl'] = $this->returnUrl;
        }
        if ($this->cancelUrl !== null) {
            $result['cancelUrl'] = $this->cancelUrl;
        }
        if ($this->metadata !== null) {
            $result['metadata'] = $this->metadata;
        }

        return $result;
    }
}
