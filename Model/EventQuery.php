<?php

namespace Omniticket\Model;

/**
 * Which events to list: a period, a keyword, past ones or not, how many.
 * A provider passes what its API can filter on and filter() applies the
 * whole query to what comes back, so every provider answers the same
 * query the same way.
 */
final readonly class EventQuery
{
    /** @param ?\DateTimeImmutable $now the present (a test's clock); the real one when null */
    public function __construct(
        public ?\DateTimeImmutable $from = null,
        public ?\DateTimeImmutable $to = null,
        public ?string $keyword = null,
        public bool $past = false,
        public ?int $limit = null,
        public ?\DateTimeImmutable $now = null,
    ) {
    }

    public function matches(Event $event): bool
    {
        $last = $event->end ?? $event->start;
        if (!$this->past && null !== $last && $last < ($this->now ?? new \DateTimeImmutable())) {
            return false;
        }
        if (null !== $this->from && null !== $last && $last < $this->from) {
            return false;
        }
        if (null !== $this->to && null !== $event->start && $event->start > $this->to) {
            return false;
        }
        if (null !== $this->keyword && '' !== $this->keyword) {
            return false !== stripos($event->title.' '.($event->venue ?? ''), $this->keyword);
        }

        return true;
    }

    /**
     * @param iterable<Event> $events
     *
     * @return \Generator<int, Event>
     */
    public function filter(iterable $events): \Generator
    {
        $count = 0;
        foreach ($events as $event) {
            if (null !== $this->limit && $count >= $this->limit) {
                return;
            }
            if ($this->matches($event)) {
                ++$count;
                yield $event;
            }
        }
    }
}
