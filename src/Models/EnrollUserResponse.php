<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class EnrollUserResponse
{
    /**
     * @param string      $userExternalId       External user identifier
     * @param string      $enrollmentHash       Hash of the enrollment data
     * @param int         $enrollmentVersion    Enrollment version number
     * @param string      $enrollmentSource     Source of the enrollment image
     * @param string      $enrolledAt           ISO 8601 enrollment timestamp
     * @param string      $cpf                  CPF number (11 digits)
     * @param float       $faceConfidence       Face detection confidence score
     * @param string|null $documentImageHash    Hash of the document image (if applicable)
     * @param float|null  $extractionConfidence Document extraction confidence (if applicable)
     */
    public function __construct(
        public readonly string $userExternalId,
        public readonly string $enrollmentHash,
        public readonly int $enrollmentVersion,
        public readonly string $enrollmentSource,
        public readonly string $enrolledAt,
        public readonly string $cpf,
        public readonly float $faceConfidence,
        public readonly ?string $documentImageHash = null,
        public readonly ?float $extractionConfidence = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userExternalId: (string) ($data['userExternalId'] ?? ''),
            enrollmentHash: (string) ($data['enrollmentHash'] ?? ''),
            enrollmentVersion: (int) ($data['enrollmentVersion'] ?? 0),
            enrollmentSource: (string) ($data['enrollmentSource'] ?? ''),
            enrolledAt: (string) ($data['enrolledAt'] ?? ''),
            cpf: (string) ($data['cpf'] ?? ''),
            faceConfidence: (float) ($data['faceConfidence'] ?? 0.0),
            documentImageHash: isset($data['documentImageHash']) ? (string) $data['documentImageHash'] : null,
            extractionConfidence: isset($data['extractionConfidence']) ? (float) $data['extractionConfidence'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'userExternalId' => $this->userExternalId,
            'enrollmentHash' => $this->enrollmentHash,
            'enrollmentVersion' => $this->enrollmentVersion,
            'enrollmentSource' => $this->enrollmentSource,
            'enrolledAt' => $this->enrolledAt,
            'cpf' => $this->cpf,
            'faceConfidence' => $this->faceConfidence,
        ];

        if ($this->documentImageHash !== null) {
            $result['documentImageHash'] = $this->documentImageHash;
        }
        if ($this->extractionConfidence !== null) {
            $result['extractionConfidence'] = $this->extractionConfidence;
        }

        return $result;
    }
}
