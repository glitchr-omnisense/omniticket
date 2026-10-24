<?php

namespace Omniticket\Exception;

/** A provider asked for what it cannot do: the attendees of a read-only catalogue, an incremental read it has no filter for. */
final class NotSupportedException extends \LogicException implements OmniticketException
{
    public static function capability(object $provider, string $name, string $capability): self
    {
        return new self(\sprintf('The "%s" provider (%s) does not %s.', $name, $provider::class, $capability));
    }
}
