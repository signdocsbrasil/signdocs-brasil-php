<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class CancelEnvelopeResponse
{
    /**
     * @param string $envelopeId           Envelope identifier.
     * @param string $status               Envelope status after cancellation (CANCELLED).
     * @param int    $cancelledCount       How many pending sessions were transitioned
     *                                     to CANCELLED, killing their signing links.
     * @param int    $preservedSignedCount How many already-completed signatures were
     *                                     left untouched. Cancelling an envelope stops
     *                                     the pending signers; it never invalidates a
     *                                     signature that was already collected, whose
     *                                     evidence stays independently verifiable.
     * @param array<int, array{sessionId?: string, transactionId?: string}> $cancelledSessions
     *                                     The sessions that were cancelled.
     * @param bool   $alreadyCancelled     True when the envelope was already CANCELLED,
     *                                     in which case cancelledCount is 0. The endpoint
     *                                     is idempotent, so re-cancelling is a safe no-op.
     */
    public function __construct(
        public readonly string $envelopeId,
        public readonly string $status,
        public readonly int $cancelledCount = 0,
        public readonly int $preservedSignedCount = 0,
        public readonly array $cancelledSessions = [],
        public readonly bool $alreadyCancelled = false,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array<int, array{sessionId?: string, transactionId?: string}> $sessions */
        $sessions = is_array($data['cancelledSessions'] ?? null) ? $data['cancelledSessions'] : [];

        return new self(
            envelopeId: (string) ($data['envelopeId'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            cancelledCount: (int) ($data['cancelledCount'] ?? 0),
            preservedSignedCount: (int) ($data['preservedSignedCount'] ?? 0),
            cancelledSessions: $sessions,
            alreadyCancelled: (bool) ($data['alreadyCancelled'] ?? false),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'envelopeId' => $this->envelopeId,
            'status' => $this->status,
            'cancelledCount' => $this->cancelledCount,
            'preservedSignedCount' => $this->preservedSignedCount,
            'cancelledSessions' => $this->cancelledSessions,
        ];

        if ($this->alreadyCancelled) {
            $result['alreadyCancelled'] = true;
        }

        return $result;
    }
}
