<?php

namespace Omniticket\Exception;

/**
 * A provider asked for what it cannot do: the attendees of a read-only
 * catalogue, an incremental read it has no filter for (capability(), as
 * omnisense/contracts words it).
 */
final class NotSupportedException extends \Omnisense\Contracts\Exception\NotSupportedException implements OmniticketException
{
}
