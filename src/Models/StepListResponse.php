<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Models;

final class StepListResponse
{
    /**
     * @param Step[] $steps List of transaction steps
     */
    public function __construct(
        public readonly array $steps,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $steps = [];

        // Handle both direct array of steps and wrapped { steps: [...] } format
        $rawSteps = $data['steps'] ?? $data;

        if (is_array($rawSteps)) {
            foreach ($rawSteps as $step) {
                if (is_array($step)) {
                    $steps[] = Step::fromArray($step);
                }
            }
        }

        return new self(steps: $steps);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'steps' => array_map(fn(Step $s) => $s->toArray(), $this->steps),
        ];
    }
}
