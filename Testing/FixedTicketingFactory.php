<?php

namespace Omniticket\Testing;

use Omniticket\Config;
use Omniticket\Model\Attendee;
use Omniticket\Model\Event;
use Omniticket\ProviderFactory;
use Omniticket\ProviderInterface;

/** FixedTicketing under the name "fixed", for a Registry. */
final class FixedTicketingFactory extends ProviderFactory
{
    /**
     * @param list<Event>                   $events
     * @param array<string, list<Attendee>> $attendees
     */
    public function __construct(private readonly array $events = [], private readonly array $attendees = [])
    {
    }

    protected function populate(Config $c): void
    {
        $c->defaults(['omniticket.factory_name' => 'fixed', 'omniticket.factory_title' => 'Fixed', 'omniticket.required_options' => []]);
    }

    protected function build(Config $c): ProviderInterface
    {
        return new FixedTicketing($this->events, $this->attendees);
    }
}
