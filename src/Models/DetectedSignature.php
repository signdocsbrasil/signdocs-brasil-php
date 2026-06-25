<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

/**
 * A single signature detected inside a verified PDF, as returned in the
 * `signatures` array of `POST /v1/verify/document`.
 */
final class DetectedSignature
{
    /**
     * @param string      $method     Detection method that surfaced the signature
     * @param string      $type       Signature type: one of `pades`, `pkcs7`, `legacy`, `digital_certificate`
     * @param float       $confidence Detection confidence (0.0 - 1.0)
     * @param string|null $subFilter  PDF signature sub-filter (e.g. `adbe.pkcs7.detached`)
     * @param string|null $filter     PDF signature filter
     */
    public function __construct(
        public readonly string $method,
        public readonly string $type,
        public readonly float $confidence,
        public readonly ?string $subFilter = null,
        public readonly ?string $filter = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            method: (string) ($data['method'] ?? ''),
            type: (string) ($data['type'] ?? ''),
            confidence: (float) ($data['confidence'] ?? 0.0),
            subFilter: isset($data['subFilter']) ? (string) $data['subFilter'] : null,
            filter: isset($data['filter']) ? (string) $data['filter'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'method' => $this->method,
            'type' => $this->type,
            'confidence' => $this->confidence,
        ];

        if ($this->subFilter !== null) {
            $result['subFilter'] = $this->subFilter;
        }
        if ($this->filter !== null) {
            $result['filter'] = $this->filter;
        }

        return $result;
    }
}
