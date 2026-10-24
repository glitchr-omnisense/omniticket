<?php

namespace Omniticket;

use Omniticket\Model\Check;

/**
 * A ticket code checked at the door: valid, already used, void, unknown.
 * Reading only: the ticket is not marked as scanned at the provider - the
 * provider's own scanning application does that.
 */
interface TicketCheckInterface extends ProviderInterface
{
    public function check(string $eventId, string $code): Check;
}
