<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class AdvanceSessionRequest
{
    /**
     * @param string                       $action              Action to perform (accept, verify_otp, etc.)
     * @param string|null                  $otpCode             OTP code (required for verify_otp)
     * @param string|null                  $livenessSessionId   Rekognition session ID (required for complete_liveness)
     * @param list<string>|null            $certificateChainPems PEM certificates (required for prepare_signing)
     * @param string|null                  $signatureRequestId  Signature request ID (required for complete_signing)
     * @param string|null                  $rawSignatureBase64  Raw signature in base64 (required for complete_signing)
     * @param array<string, mixed>|null    $geolocation         Geolocation data
     */
    public function __construct(
        public readonly string $action,
        public readonly ?string $otpCode = null,
        public readonly ?string $livenessSessionId = null,
        public readonly ?array $certificateChainPems = null,
        public readonly ?string $signatureRequestId = null,
        public readonly ?string $rawSignatureBase64 = null,
        public readonly ?array $geolocation = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = ['action' => $this->action];

        if ($this->otpCode !== null) {
            $result['otpCode'] = $this->otpCode;
        }
        if ($this->livenessSessionId !== null) {
            $result['livenessSessionId'] = $this->livenessSessionId;
        }
        if ($this->certificateChainPems !== null) {
            $result['certificateChainPems'] = $this->certificateChainPems;
        }
        if ($this->signatureRequestId !== null) {
            $result['signatureRequestId'] = $this->signatureRequestId;
        }
        if ($this->rawSignatureBase64 !== null) {
            $result['rawSignatureBase64'] = $this->rawSignatureBase64;
        }
        if ($this->geolocation !== null) {
            $result['geolocation'] = $this->geolocation;
        }

        return $result;
    }
}
