<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class Signer
{
    public function __construct(
        public readonly string $name,
        public readonly string $userExternalId,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $displayName = null,
        public readonly ?string $cpf = null,
        public readonly ?string $cnpj = null,
        public readonly ?string $birthDate = null,
        public readonly ?string $otpChannel = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            userExternalId: (string) ($data['userExternalId'] ?? ''),
            email: isset($data['email']) ? (string) $data['email'] : null,
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            displayName: isset($data['displayName']) ? (string) $data['displayName'] : null,
            cpf: isset($data['cpf']) ? (string) $data['cpf'] : null,
            cnpj: isset($data['cnpj']) ? (string) $data['cnpj'] : null,
            birthDate: isset($data['birthDate']) ? (string) $data['birthDate'] : null,
            otpChannel: isset($data['otpChannel']) ? (string) $data['otpChannel'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'name' => $this->name,
            'userExternalId' => $this->userExternalId,
        ];

        if ($this->email !== null) {
            $result['email'] = $this->email;
        }
        if ($this->displayName !== null) {
            $result['displayName'] = $this->displayName;
        }
        if ($this->cpf !== null) {
            $result['cpf'] = $this->cpf;
        }
        if ($this->cnpj !== null) {
            $result['cnpj'] = $this->cnpj;
        }
        if ($this->phone !== null) {
            $result['phone'] = $this->phone;
        }
        if ($this->birthDate !== null) {
            $result['birthDate'] = $this->birthDate;
        }
        if ($this->otpChannel !== null) {
            $result['otpChannel'] = $this->otpChannel;
        }

        return $result;
    }
}
