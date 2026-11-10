<?php

namespace Omniticket;

/**
 * One ticketing service, configured: Weezevent, Billetweb, HelloAsso...
 * Its Capabilities say what it can be asked - list and read events
 * (EventCatalogInterface), list who comes (AttendeeListInterface), check a
 * ticket code (TicketCheckInterface) - and its usage policy: its rate
 * limits, what its terms allow (omnisense/contracts' provider underneath).
 */
interface ProviderInterface extends \Omnisense\Contracts\Provider\ProviderInterface
{
    /** The factory's name: "weezevent", "billetweb"... */
    public function getName(): string;

    public function getTitle(): string;

    public function capabilities(): Capabilities;
}
