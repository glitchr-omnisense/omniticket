<?php

namespace Omniticket\Testing;

use Omniticket\AttendeeListInterface;
use Omniticket\Capabilities;
use Omniticket\EventCatalogInterface;
use Omniticket\Exception\NotFoundException;
use Omniticket\Exception\NotSupportedException;
use Omniticket\Model\Attendee;
use Omniticket\Model\Check;
use Omniticket\Model\Event;
use Omniticket\Model\EventQuery;
use Omniticket\TicketCheckInterface;

/**
 * A provider that answers from what it was given: an application's tests,
 * a demo, the harness's bare script. Every contract, no network.
 */
final class FixedTicketing implements EventCatalogInterface, AttendeeListInterface, TicketCheckInterface
{
    /**
     * @param list<Event>                   $events
     * @param array<string, list<Attendee>> $attendees by event id
     */
    public function __construct(private readonly array $events = [], private readonly array $attendees = [])
    {
    }

    public function getName(): string
    {
        return 'fixed';
    }

    public function getTitle(): string
    {
        return 'Fixed';
    }

    public function capabilities(): Capabilities
    {
        return new Capabilities(catalog: true, attendees: true, check: true, incremental: false);
    }

    public function events(EventQuery $query = new EventQuery()): iterable
    {
        return $query->filter($this->events);
    }

    public function event(string $id): Event
    {
        foreach ($this->events as $event) {
            if ($event->id === $id) {
                return $event;
            }
        }

        throw NotFoundException::of('Fixed', 'event "'.$id.'"');
    }

    /** Every attendee given for the event; not incremental: a read since a date is refused. */
    public function attendees(string $eventId, ?\DateTimeImmutable $updatedSince = null): iterable
    {
        if (null !== $updatedSince) {
            throw NotSupportedException::capability($this, 'fixed', 'read attendees changed since a moment (it is not incremental)');
        }

        return $this->attendees[$eventId] ?? [];
    }

    public function check(string $eventId, string $code): Check
    {
        return Check::among($this->attendees($eventId), $code);
    }
}
