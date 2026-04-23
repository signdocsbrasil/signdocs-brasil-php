<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class CreateEnvelopeRequest
{
    /**
     * @param string                     $signingMode      Signing mode (PARALLEL or SEQUENTIAL).
     * @param int                        $totalSigners     Total number of signers expected (minimum 2).
     * @param array<string, mixed>       $document         Inline document: `['content' => '<base64>', 'filename' => '...']`.
     * @param array<string, string>|null $metadata         Arbitrary key-value metadata.
     * @param string|null                $locale           Default locale for envelope sessions (pt-BR, en, es).
     * @param string|null                $returnUrl        Default return URL after session completion.
     * @param string|null                $cancelUrl        Default cancel URL.
     * @param int|null                   $expiresInMinutes Envelope expiration in minutes (minimum 5; default 1440).
     * @param Owner|null                 $owner            Identity of the requester (see {@see Owner}); when set, every session added to the envelope auto-dispatches an invite email to the signer (if their email differs from the owner's) and the owner receives per-signer and final completion notifications.
     */
    public function __construct(
        public readonly string $signingMode,
        public readonly int $totalSigners,
        public readonly array $document,
        public readonly ?array $metadata = null,
        public readonly ?string $locale = null,
        public readonly ?string $returnUrl = null,
        public readonly ?string $cancelUrl = null,
        public readonly ?int $expiresInMinutes = null,
        public readonly ?Owner $owner = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array<string, mixed> $document */
        $document = is_array($data['document'] ?? null) ? $data['document'] : [];

        return new self(
            signingMode: (string) ($data['signingMode'] ?? ''),
            totalSigners: (int) ($data['totalSigners'] ?? 0),
            document: $document,
            metadata: $data['metadata'] ?? null,
            locale: isset($data['locale']) ? (string) $data['locale'] : null,
            returnUrl: isset($data['returnUrl']) ? (string) $data['returnUrl'] : null,
            cancelUrl: isset($data['cancelUrl']) ? (string) $data['cancelUrl'] : null,
            expiresInMinutes: isset($data['expiresInMinutes']) ? (int) $data['expiresInMinutes'] : null,
            owner: isset($data['owner']) && is_array($data['owner']) ? Owner::fromArray($data['owner']) : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'signingMode' => $this->signingMode,
            'totalSigners' => $this->totalSigners,
            'document' => $this->document,
        ];

        if ($this->metadata !== null) {
            $result['metadata'] = $this->metadata;
        }
        if ($this->locale !== null) {
            $result['locale'] = $this->locale;
        }
        if ($this->returnUrl !== null) {
            $result['returnUrl'] = $this->returnUrl;
        }
        if ($this->cancelUrl !== null) {
            $result['cancelUrl'] = $this->cancelUrl;
        }
        if ($this->expiresInMinutes !== null) {
            $result['expiresInMinutes'] = $this->expiresInMinutes;
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
