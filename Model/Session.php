<?php

namespace Omniticket\Model;

/** One date of an event sold for several (a "séance"). */
final readonly class Session
{
    public function __construct(
        public string $id,
        public ?\DateTimeImmutable $start = null,
        public ?\DateTimeImmutable $end = null,
        public ?string $name = null,
        public ?string $venue = null,
        public Availability $availability = new Availability(),
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['id' => $this->id, 'start' => $this->start?->format(\DATE_ATOM), 'end' => $this->end?->format(\DATE_ATOM), 'name' => $this->name, 'venue' => $this->venue, 'availability' => $this->availability->toArray()];
    }
}
