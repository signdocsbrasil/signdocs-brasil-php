<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Errors;

/**
 * RFC 7807 Problem Detail value object.
 */
final class ProblemDetail
{
    /**
     * @param string               $type      A URI reference that identifies the problem type
     * @param string               $title     A short, human-readable summary of the problem type
     * @param int                  $status    The HTTP status code
     * @param string|null          $detail    A human-readable explanation specific to this occurrence
     * @param string|null          $instance  A URI reference that identifies the specific occurrence
     * @param array<string, mixed> $extensions Additional members in the problem detail
     */
    public function __construct(
        public readonly string $type,
        public readonly string $title,
        public readonly int $status,
        public readonly ?string $detail = null,
        public readonly ?string $instance = null,
        public readonly array $extensions = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $known = ['type', 'title', 'status', 'detail', 'instance'];
        $extensions = array_diff_key($data, array_flip($known));

        return new self(
            type: (string) ($data['type'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            status: (int) ($data['status'] ?? 0),
            detail: isset($data['detail']) ? (string) $data['detail'] : null,
            instance: isset($data['instance']) ? (string) $data['instance'] : null,
            extensions: $extensions,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->status,
        ];

        if ($this->detail !== null) {
            $result['detail'] = $this->detail;
        }
        if ($this->instance !== null) {
            $result['instance'] = $this->instance;
        }

        return array_merge($result, $this->extensions);
    }
}
