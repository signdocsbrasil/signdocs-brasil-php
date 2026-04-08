<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class SigningSessionListResponse
{
    /**
     * @param SigningSession[] $sessions
     * @param int              $count
     * @param string|null      $nextToken
     */
    public function __construct(
        public readonly array $sessions,
        public readonly int $count,
        public readonly ?string $nextToken = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $sessions = [];
        if (isset($data['sessions']) && is_array($data['sessions'])) {
            foreach ($data['sessions'] as $session) {
                $sessions[] = SigningSession::fromArray($session);
            }
        }

        return new self(
            sessions: $sessions,
            count: (int) ($data['count'] ?? 0),
            nextToken: isset($data['nextToken']) ? (string) $data['nextToken'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'sessions' => array_map(fn(SigningSession $s) => $s->toArray(), $this->sessions),
            'count' => $this->count,
        ];

        if ($this->nextToken !== null) {
            $result['nextToken'] = $this->nextToken;
        }

        return $result;
    }
}
