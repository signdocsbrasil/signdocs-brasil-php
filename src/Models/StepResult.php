<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class StepResult
{
    /**
     * @param array<string, mixed>|null $liveness           Liveness check result
     * @param array<string, mixed>|null $match              Biometric match result
     * @param array<string, mixed>|null $otp                OTP verification result
     * @param array<string, mixed>|null $click              Click acceptance result
     * @param array<string, mixed>|null $purposeDisclosure  Purpose disclosure result
     * @param array<string, mixed>|null $digitalSignature   Digital signature result
     * @param array<string, mixed>|null $serproIdentity     SERPRO identity check result
     * @param array<string, mixed>|null $geolocation        Geolocation data
     * @param array<string, mixed>|null $documentPhotoMatch Document photo match result
     * @param array<string, mixed>|null         $quality                Image quality result
     * @param GovernmentDbValidation|null       $governmentDbValidation Government DB validation result
     * @param string|null                       $providerTimestamp      Provider timestamp
     */
    public function __construct(
        public readonly ?array $liveness = null,
        public readonly ?array $match = null,
        public readonly ?array $otp = null,
        public readonly ?array $click = null,
        public readonly ?array $purposeDisclosure = null,
        public readonly ?array $digitalSignature = null,
        public readonly ?array $serproIdentity = null,
        public readonly ?array $geolocation = null,
        public readonly ?array $documentPhotoMatch = null,
        public readonly ?array $quality = null,
        public readonly ?GovernmentDbValidation $governmentDbValidation = null,
        public readonly ?string $providerTimestamp = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            liveness: $data['liveness'] ?? null,
            match: $data['match'] ?? null,
            otp: $data['otp'] ?? null,
            click: $data['click'] ?? null,
            purposeDisclosure: $data['purposeDisclosure'] ?? null,
            digitalSignature: $data['digitalSignature'] ?? null,
            serproIdentity: $data['serproIdentity'] ?? null,
            geolocation: $data['geolocation'] ?? null,
            documentPhotoMatch: $data['documentPhotoMatch'] ?? null,
            quality: $data['quality'] ?? null,
            governmentDbValidation: isset($data['governmentDbValidation'])
                ? GovernmentDbValidation::fromArray($data['governmentDbValidation'])
                : null,
            providerTimestamp: isset($data['providerTimestamp']) ? (string) $data['providerTimestamp'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];

        if ($this->liveness !== null) {
            $result['liveness'] = $this->liveness;
        }
        if ($this->match !== null) {
            $result['match'] = $this->match;
        }
        if ($this->otp !== null) {
            $result['otp'] = $this->otp;
        }
        if ($this->click !== null) {
            $result['click'] = $this->click;
        }
        if ($this->purposeDisclosure !== null) {
            $result['purposeDisclosure'] = $this->purposeDisclosure;
        }
        if ($this->digitalSignature !== null) {
            $result['digitalSignature'] = $this->digitalSignature;
        }
        if ($this->serproIdentity !== null) {
            $result['serproIdentity'] = $this->serproIdentity;
        }
        if ($this->geolocation !== null) {
            $result['geolocation'] = $this->geolocation;
        }
        if ($this->documentPhotoMatch !== null) {
            $result['documentPhotoMatch'] = $this->documentPhotoMatch;
        }
        if ($this->quality !== null) {
            $result['quality'] = $this->quality;
        }
        if ($this->governmentDbValidation !== null) {
            $result['governmentDbValidation'] = $this->governmentDbValidation->toArray();
        }
        if ($this->providerTimestamp !== null) {
            $result['providerTimestamp'] = $this->providerTimestamp;
        }

        return $result;
    }
}
