<?php

namespace Omniticket\Exception;

use Omnisense\Contracts\Exception\ExceptionInterface;

/** Every exception omniticket throws (and omnisense/contracts' interface, so one catch holds every family's). */
interface OmniticketException extends ExceptionInterface
{
}
