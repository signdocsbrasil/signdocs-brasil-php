<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

/**
 * Identity of the requester creating a signing session or envelope,
 * distinct from the signer(s). When provided, SignDocs automatically:
 *
 *   1. Emails each signer an invitation with their signing URL — when
 *      signer.email differs from owner.email (case-insensitive).
 *   2. Emails the owner a completion notification per signer completion
 *      (and a final "all signed" message for envelopes).
 *
 * Omit `owner` to keep the traditional behavior: the caller delivers
 * signing URLs via their own channels and relies on webhooks for
 * completion state.
 */
final class Owner
{
    /**
     * @param string|null $email Owner email. Receives completion notifications.
     * @param string|null $name  Owner name (used in email greetings).
     */
    public function __construct(
        public readonly ?string $email = null,
        public readonly ?string $name = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: isset($data['email']) ? (string) $data['email'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];

        if ($this->email !== null) {
            $result['email'] = $this->email;
        }
        if ($this->name !== null) {
            $result['name'] = $this->name;
        }

        return $result;
    }
}
