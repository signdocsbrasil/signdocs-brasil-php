<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class SigningSessionBootstrap
{
    /**
     * @param string                         $sessionId
     * @param string                         $transactionId
     * @param string                         $status
     * @param string                         $purpose
     * @param array<string, mixed>           $signer
     * @param list<array<string, mixed>>     $steps
     * @param string                         $locale
     * @param string                         $expiresAt
     * @param array<string, mixed>|null      $document
     * @param array<string, mixed>|null      $action
     * @param array<string, mixed>|null      $appearance
     * @param string|null                    $returnUrl
     * @param string|null                    $cancelUrl
     */
    public function __construct(
        public readonly string $sessionId,
        public readonly string $transactionId,
        public readonly string $status,
        public readonly string $purpose,
        public readonly array $signer,
        public readonly array $steps,
        public readonly string $locale,
        public readonly string $expiresAt,
        public readonly ?array $document = null,
        public readonly ?array $action = null,
        public readonly ?array $appearance = null,
        public readonly ?string $returnUrl = null,
        public readonly ?string $cancelUrl = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            sessionId: (string) ($data['sessionId'] ?? ''),
            transactionId: (string) ($data['transactionId'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            purpose: (string) ($data['purpose'] ?? ''),
            signer: $data['signer'] ?? [],
            steps: $data['steps'] ?? [],
            locale: (string) ($data['locale'] ?? 'pt-BR'),
            expiresAt: (string) ($data['expiresAt'] ?? ''),
            document: $data['document'] ?? null,
            action: $data['action'] ?? null,
            appearance: $data['appearance'] ?? null,
            returnUrl: isset($data['returnUrl']) ? (string) $data['returnUrl'] : null,
            cancelUrl: isset($data['cancelUrl']) ? (string) $data['cancelUrl'] : null,
        );
    }
}
