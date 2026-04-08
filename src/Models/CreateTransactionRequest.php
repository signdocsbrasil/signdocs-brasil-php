<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class CreateTransactionRequest
{
    /**
     * @param string                       $purpose           Transaction purpose (DOCUMENT_SIGNATURE or ACTION_AUTHENTICATION)
     * @param Policy                       $policy            Signing policy
     * @param Signer                       $signer            Signer information
     * @param array<string, mixed>|null    $document          Document with 'content' (base64) and optional 'filename'
     * @param array<string, mixed>|null    $action            Action metadata (type, description, reference)
     * @param array<string, mixed>|null    $digitalSignature  Digital signature metadata
     * @param string|null                  $documentGroupId   Group ID for multi-signer workflows
     * @param int|null                     $signerIndex       Index of this signer in the group
     * @param int|null                     $totalSigners      Total number of signers in the group
     * @param array<string, string>|null   $metadata          Arbitrary key-value metadata
     * @param int|null                     $expiresInMinutes  Custom expiration time in minutes
     */
    public function __construct(
        public readonly string $purpose,
        public readonly Policy $policy,
        public readonly Signer $signer,
        public readonly ?array $document = null,
        public readonly ?array $action = null,
        public readonly ?array $digitalSignature = null,
        public readonly ?string $documentGroupId = null,
        public readonly ?int $signerIndex = null,
        public readonly ?int $totalSigners = null,
        public readonly ?array $metadata = null,
        public readonly ?int $expiresInMinutes = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            purpose: (string) ($data['purpose'] ?? ''),
            policy: Policy::fromArray($data['policy'] ?? []),
            signer: Signer::fromArray($data['signer'] ?? []),
            document: $data['document'] ?? null,
            action: $data['action'] ?? null,
            digitalSignature: $data['digitalSignature'] ?? null,
            documentGroupId: isset($data['documentGroupId']) ? (string) $data['documentGroupId'] : null,
            signerIndex: isset($data['signerIndex']) ? (int) $data['signerIndex'] : null,
            totalSigners: isset($data['totalSigners']) ? (int) $data['totalSigners'] : null,
            metadata: $data['metadata'] ?? null,
            expiresInMinutes: isset($data['expiresInMinutes']) ? (int) $data['expiresInMinutes'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'purpose' => $this->purpose,
            'policy' => $this->policy->toArray(),
            'signer' => $this->signer->toArray(),
        ];

        if ($this->document !== null) {
            $result['document'] = $this->document;
        }
        if ($this->action !== null) {
            $result['action'] = $this->action;
        }
        if ($this->digitalSignature !== null) {
            $result['digitalSignature'] = $this->digitalSignature;
        }
        if ($this->documentGroupId !== null) {
            $result['documentGroupId'] = $this->documentGroupId;
        }
        if ($this->signerIndex !== null) {
            $result['signerIndex'] = $this->signerIndex;
        }
        if ($this->totalSigners !== null) {
            $result['totalSigners'] = $this->totalSigners;
        }
        if ($this->metadata !== null) {
            $result['metadata'] = $this->metadata;
        }
        if ($this->expiresInMinutes !== null) {
            $result['expiresInMinutes'] = $this->expiresInMinutes;
        }

        return $result;
    }
}
