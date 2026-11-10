<?php

namespace Omniticket\Policy;

use Omnisense\Contracts\Policy\PolicyGuard;
use Omniticket\AttendeeListInterface;

/**
 * A provider's attendee list behind its guard: each call is the operation
 * "attendees". A listing is one call, checked before its first page; the
 * pages it fetches as the iteration goes are watched by the provider's
 * Backoff (a 429 holds the next ones).
 *
 * @extends Guarded<AttendeeListInterface>
 */
final class GuardedAttendees extends Guarded implements AttendeeListInterface
{
    public function __construct(AttendeeListInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function attendees(string $eventId, ?\DateTimeImmutable $updatedSince = null): iterable
    {
        return $this->guard->call('attendees', fn () => $this->provider->attendees($eventId, $updatedSince));
    }
}
