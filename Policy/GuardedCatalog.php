<?php

namespace Omniticket\Policy;

use Omnisense\Contracts\Policy\PolicyGuard;
use Omniticket\EventCatalogInterface;
use Omniticket\Model\Event;
use Omniticket\Model\EventQuery;

/**
 * A provider's catalogue behind its guard: each call is the operation named
 * after its method, "events" or "event". A listing is one call, checked
 * before its first page; the pages it fetches as the iteration goes are
 * watched by the provider's Backoff (a 429 holds the next ones).
 *
 * @extends Guarded<EventCatalogInterface>
 */
final class GuardedCatalog extends Guarded implements EventCatalogInterface
{
    public function __construct(EventCatalogInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function events(EventQuery $query = new EventQuery()): iterable
    {
        return $this->guard->call('events', fn () => $this->provider->events($query));
    }

    public function event(string $id): Event
    {
        return $this->guard->call('event', fn () => $this->provider->event($id));
    }
}
