<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class CompleteSigningResponse
{
    /**
     * @param string               $stepId
     * @param string               $status
     * @param array<string, mixed> $result Contains 'digitalSignature' sub-object
     */
    public function __construct(
        public readonly string $stepId,
        public readonly string $status,
        public readonly array $result,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            stepId: (string) ($data['stepId'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            result: $data['result'] ?? [],
        );
    }

    /**
     * ICP-Brasil signature timestamp (carimbo do tempo) embedded in the signature, when the
     * tenant has the feature: keys genTime, tsaName, serial, policyOid, tokenSha256.
     * genTime is the time attested by the ACT; digitalSignature.signedAt remains the
     * SignDocs server time.
     *
     * @return array<string, string>|null
     */
    public function signatureTimestamp(): ?array
    {
        $ts = $this->result['digitalSignature']['signatureTimestamp'] ?? null;

        return is_array($ts) ? $ts : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'stepId' => $this->stepId,
            'status' => $this->status,
            'result' => $this->result,
        ];
    }
}
