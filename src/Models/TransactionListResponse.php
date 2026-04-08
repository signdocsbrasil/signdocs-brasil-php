<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class TransactionListResponse
{
    /**
     * @param Transaction[] $transactions
     * @param int           $count
     * @param string|null   $nextToken
     */
    public function __construct(
        public readonly array $transactions,
        public readonly int $count,
        public readonly ?string $nextToken = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $transactions = [];
        if (isset($data['transactions']) && is_array($data['transactions'])) {
            foreach ($data['transactions'] as $tx) {
                $transactions[] = Transaction::fromArray($tx);
            }
        }

        return new self(
            transactions: $transactions,
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
            'transactions' => array_map(fn(Transaction $tx) => $tx->toArray(), $this->transactions),
            'count' => $this->count,
        ];

        if ($this->nextToken !== null) {
            $result['nextToken'] = $this->nextToken;
        }

        return $result;
    }
}
