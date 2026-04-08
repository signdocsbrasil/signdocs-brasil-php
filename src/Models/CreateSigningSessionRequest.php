<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class CreateSigningSessionRequest
{
    /**
     * @param string                       $name              Session name/title
     * @param string                       $type              Session type
     * @param array<int, array<string, mixed>>  $signers      Signer definitions
     * @param array<int, array<string, mixed>>  $documents    Document definitions
     * @param string|null                  $callbackUrl       Webhook callback URL
     * @param string|null                  $redirectUrl       Redirect URL after completion
     * @param int|null                     $expiresInMinutes  Custom expiration time in minutes
     * @param array<string, string>|null   $metadata          Arbitrary key-value metadata
     * @param string|null                  $locale            Locale for the signing UI
     * @param string|null                  $brandingId        Custom branding ID
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly array $signers,
        public readonly array $documents,
        public readonly ?string $callbackUrl = null,
        public readonly ?string $redirectUrl = null,
        public readonly ?int $expiresInMinutes = null,
        public readonly ?array $metadata = null,
        public readonly ?string $locale = null,
        public readonly ?string $brandingId = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            type: (string) ($data['type'] ?? ''),
            signers: $data['signers'] ?? [],
            documents: $data['documents'] ?? [],
            callbackUrl: isset($data['callbackUrl']) ? (string) $data['callbackUrl'] : null,
            redirectUrl: isset($data['redirectUrl']) ? (string) $data['redirectUrl'] : null,
            expiresInMinutes: isset($data['expiresInMinutes']) ? (int) $data['expiresInMinutes'] : null,
            metadata: $data['metadata'] ?? null,
            locale: isset($data['locale']) ? (string) $data['locale'] : null,
            brandingId: isset($data['brandingId']) ? (string) $data['brandingId'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'name' => $this->name,
            'type' => $this->type,
            'signers' => $this->signers,
            'documents' => $this->documents,
        ];

        if ($this->callbackUrl !== null) {
            $result['callbackUrl'] = $this->callbackUrl;
        }
        if ($this->redirectUrl !== null) {
            $result['redirectUrl'] = $this->redirectUrl;
        }
        if ($this->expiresInMinutes !== null) {
            $result['expiresInMinutes'] = $this->expiresInMinutes;
        }
        if ($this->metadata !== null) {
            $result['metadata'] = $this->metadata;
        }
        if ($this->locale !== null) {
            $result['locale'] = $this->locale;
        }
        if ($this->brandingId !== null) {
            $result['brandingId'] = $this->brandingId;
        }

        return $result;
    }
}
