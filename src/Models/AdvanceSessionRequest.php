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
     * @param string|null                  $otpChannel          OTP delivery channel (sms, email, whatsapp)
     * @param string|null                  $cpfCnpj             CPF/CNPJ the signer types to confirm identity (confirm_signer)
     * @param string|null                  $documentImage       Base64 identity-document photo, max 5MB (complete_document_photo)
     * @param string|null                  $documentType        Type of the document sent in $documentImage
     * @param float|null                   $sandboxSimilarity   Sandbox-only simulated scores, so a rejection can be
     * @param float|null                   $sandboxLivenessConfidence rehearsed. Read only once the step already resolved
     * @param float|null                   $sandboxBrightness   to sandbox — they can never make a real verification
     * @param float|null                   $sandboxSharpness    pass.
     * @param array<string, mixed>|null    $deviceInfo          Device characteristics, recorded in the evidence
     */
    public function __construct(
        public readonly string $action,
        public readonly ?string $otpCode = null,
        public readonly ?string $livenessSessionId = null,
        public readonly ?array $certificateChainPems = null,
        public readonly ?string $signatureRequestId = null,
        public readonly ?string $rawSignatureBase64 = null,
        public readonly ?array $geolocation = null,
        public readonly ?string $otpChannel = null,
        public readonly ?string $cpfCnpj = null,
        public readonly ?string $documentImage = null,
        public readonly ?string $documentType = null,
        public readonly ?float $sandboxSimilarity = null,
        public readonly ?float $sandboxLivenessConfidence = null,
        public readonly ?float $sandboxBrightness = null,
        public readonly ?float $sandboxSharpness = null,
        public readonly ?array $deviceInfo = null,
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
        if ($this->otpChannel !== null) {
            $result['otpChannel'] = $this->otpChannel;
        }
        if ($this->cpfCnpj !== null) {
            $result['cpfCnpj'] = $this->cpfCnpj;
        }
        if ($this->documentImage !== null) {
            $result['documentImage'] = $this->documentImage;
        }
        if ($this->documentType !== null) {
            $result['documentType'] = $this->documentType;
        }
        if ($this->sandboxSimilarity !== null) {
            $result['sandboxSimilarity'] = $this->sandboxSimilarity;
        }
        if ($this->sandboxLivenessConfidence !== null) {
            $result['sandboxLivenessConfidence'] = $this->sandboxLivenessConfidence;
        }
        if ($this->sandboxBrightness !== null) {
            $result['sandboxBrightness'] = $this->sandboxBrightness;
        }
        if ($this->sandboxSharpness !== null) {
            $result['sandboxSharpness'] = $this->sandboxSharpness;
        }
        if ($this->deviceInfo !== null) {
            $result['deviceInfo'] = $this->deviceInfo;
        }

        return $result;
    }
}
