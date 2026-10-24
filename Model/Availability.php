<?php

namespace Omniticket\Model;

/**
 * How many places are left, as far as the provider says: an unknown
 * remainder is null, not zero; a sale without a limit is unlimited.
 */
final readonly class Availability
{
    public function __construct(
        public ?int $remaining = null,
        public ?int $capacity = null,
        public ?int $sold = null,
        public bool $soldOut = false,
        public bool $unlimited = false,
    ) {
    }

    /** From counts: sold out when nothing remains of a limited capacity. */
    public static function of(?int $remaining, ?int $capacity = null, ?int $sold = null): self
    {
        if (null === $remaining && null !== $capacity && null !== $sold) {
            $remaining = max(0, $capacity - $sold);
        }

        return new self($remaining, $capacity, $sold, 0 === $remaining);
    }

    public static function unlimited(?int $sold = null): self
    {
        return new self(null, null, $sold, false, true);
    }

    /** Whether the provider said anything about it. */
    public function known(): bool
    {
        return $this->unlimited || $this->soldOut || null !== $this->remaining;
    }

    /** @return array{remaining: ?int, capacity: ?int, sold: ?int, soldOut: bool, unlimited: bool} */
    public function toArray(): array
    {
        return ['remaining' => $this->remaining, 'capacity' => $this->capacity, 'sold' => $this->sold, 'soldOut' => $this->soldOut, 'unlimited' => $this->unlimited];
    }
}
