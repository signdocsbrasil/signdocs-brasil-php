<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class DownloadResponse
{
    /**
     * @param string      $transactionId  Transaction identifier
     * @param string|null $documentHash   Hash of the document
     * @param string|null $originalUrl    Download URL for the original document
     * @param string|null $signedUrl      Download URL for the signed document. Present
     *                                    for PDF transactions (`documentFormat: 'pdf'`),
     *                                    where the signature is embedded in the PDF.
     * @param int         $expiresIn      Expiration time in seconds
     * @param string|null $signatureUrl   Download URL for the *detached* CAdES
     *                                    signature (`.p7s`). Present instead of
     *                                    `signedUrl` for non-PDF transactions
     *                                    (`documentFormat: 'generic'`), where the
     *                                    signature cannot be embedded.
     *
     *                                    Caveat: the API presigns this key without
     *                                    checking that the object exists, so a
     *                                    non-PDF signed under a click/OTP policy
     *                                    still returns a URL here — one that 404s,
     *                                    because only the digital-certificate step
     *                                    writes a `.p7s`. Decide from the policy,
     *                                    not from this field being set.
     * @param string|null $documentFormat `'pdf'` or `'generic'`, derived by the API
     *                                    from the uploaded bytes (not the filename).
     */
    public function __construct(
        public readonly string $transactionId,
        public readonly ?string $documentHash = null,
        public readonly ?string $originalUrl = null,
        public readonly ?string $signedUrl = null,
        public readonly int $expiresIn = 0,
        public readonly ?string $signatureUrl = null,
        public readonly ?string $documentFormat = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionId: (string) ($data['transactionId'] ?? ''),
            documentHash: isset($data['documentHash']) ? (string) $data['documentHash'] : null,
            originalUrl: isset($data['originalUrl']) ? (string) $data['originalUrl'] : null,
            signedUrl: isset($data['signedUrl']) ? (string) $data['signedUrl'] : null,
            expiresIn: (int) ($data['expiresIn'] ?? 0),
            signatureUrl: isset($data['signatureUrl']) ? (string) $data['signatureUrl'] : null,
            documentFormat: isset($data['documentFormat']) ? (string) $data['documentFormat'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'transactionId' => $this->transactionId,
            'expiresIn' => $this->expiresIn,
        ];

        if ($this->documentHash !== null) {
            $result['documentHash'] = $this->documentHash;
        }
        if ($this->originalUrl !== null) {
            $result['originalUrl'] = $this->originalUrl;
        }
        if ($this->signedUrl !== null) {
            $result['signedUrl'] = $this->signedUrl;
        }
        if ($this->signatureUrl !== null) {
            $result['signatureUrl'] = $this->signatureUrl;
        }
        if ($this->documentFormat !== null) {
            $result['documentFormat'] = $this->documentFormat;
        }

        return $result;
    }
}
