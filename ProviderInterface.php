<?php

namespace Omniticket;

/**
 * One ticketing service, configured: Weezevent, Billetweb, HelloAsso...
 * Its Capabilities say what it can be asked - list and read events
 * (EventCatalogInterface), list who comes (AttendeeListInterface), check a
 * ticket code (TicketCheckInterface).
 */
interface ProviderInterface
{
    /** The factory's name: "weezevent", "billetweb"... */
    public function getName(): string;

    public function getTitle(): string;

    public function capabilities(): Capabilities;
}
