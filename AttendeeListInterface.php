<?php

namespace Omniticket;

use Omniticket\Model\Attendee;

/** Who comes to an event: one Attendee per ticket. */
interface AttendeeListInterface extends ProviderInterface
{
    /**
     * Every ticket of the event, cancelled and refunded ones included (their
     * status says so). With $updatedSince, only those created or changed
     * since then - the incremental read providers ask for
     * (Capabilities::$incremental; NotSupportedException otherwise).
     *
     * @return iterable<Attendee>
     */
    public function attendees(string $eventId, ?\DateTimeImmutable $updatedSince = null): iterable;
}
