<?php

namespace Omniticket;

use Omniticket\Model\Event;
use Omniticket\Model\EventQuery;

/** The events a ticketing service sells: dates, rates, places left, where to buy. */
interface EventCatalogInterface extends ProviderInterface
{
    /**
     * The account's events (or the public ones, for a public catalogue), as
     * the query selects them - upcoming ones by default. Pages are fetched
     * as the iteration goes.
     *
     * @return iterable<Event>
     */
    public function events(EventQuery $query = new EventQuery()): iterable;

    /**
     * One event, whole: its rates, sessions and places left when the
     * provider has them.
     *
     * @throws Exception\NotFoundException when the provider has no such event
     */
    public function event(string $id): Event;
}
