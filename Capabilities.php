<?php

namespace Omniticket;

/**
 * What a provider is, said before it is asked anything: what it can read,
 * and the terms and limits whoever runs it must keep.
 */
final readonly class Capabilities
{
    /**
     * @param bool         $catalog     it lists and reads events (EventCatalogInterface)
     * @param bool         $attendees   it lists an event's attendees (AttendeeListInterface)
     * @param bool         $check       it checks a ticket code (TicketCheckInterface)
     * @param bool         $incremental its attendees can be read from a moment on (only those changed since)
     * @param bool         $widget      its events carry a purchase module to embed (Event::$widgetUrl)
     * @param list<string> $terms       the terms whoever runs it must follow (URLs)
     * @param list<string> $limits      the rate limits and usage rules the provider states, in words
     */
    public function __construct(
        public bool $catalog = false,
        public bool $attendees = false,
        public bool $check = false,
        public bool $incremental = false,
        public bool $widget = false,
        public array $terms = [],
        public array $limits = [],
    ) {
    }

    /** @return list<string> what it can be asked, by contract: catalog, attendees, check */
    public function contracts(): array
    {
        return array_keys(array_filter(['catalog' => $this->catalog, 'attendees' => $this->attendees, 'check' => $this->check]));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['contracts' => $this->contracts(), 'catalog' => $this->catalog, 'attendees' => $this->attendees, 'check' => $this->check, 'incremental' => $this->incremental, 'widget' => $this->widget, 'terms' => $this->terms, 'limits' => $this->limits];
    }
}
