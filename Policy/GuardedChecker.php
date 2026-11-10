<?php

namespace Omniticket\Policy;

use Omnisense\Contracts\Policy\PolicyGuard;
use Omniticket\Model\Check;
use Omniticket\TicketCheckInterface;

/**
 * A provider's ticket check behind its guard: each call is the operation
 * "check".
 *
 * @extends Guarded<TicketCheckInterface>
 */
final class GuardedChecker extends Guarded implements TicketCheckInterface
{
    public function __construct(TicketCheckInterface $provider, PolicyGuard $guard)
    {
        parent::__construct($provider, $guard);
    }

    public function check(string $eventId, string $code): Check
    {
        return $this->guard->call('check', fn () => $this->provider->check($eventId, $code));
    }
}
