<?php

namespace Omniticket;

use Omniticket\Exception\InvalidConfigException;

/**
 * A provider's configuration while its factory builds it: the options
 * given, the factory's defaults under them, and the keys that must be
 * filled - omnisense/contracts' Config, with omniticket's prefix
 * ("omniticket.factory_name", "omniticket.backoff"...) and its
 * InvalidConfigException. Every provider takes two options besides its own:
 * "policy" (the site's tightening of its usage policy) and "usage" (what
 * the site says of itself: commercial, open_source, contract). guard() is
 * the kernel's: a guard for the requests a provider makes inside its
 * operations (a token renewed, a listing's further pages), under the "plan"
 * and "policy" options, in the registry's limiter, throwing omniticket's
 * UsageLimitReachedException (the Backoff's, from the factory).
 */
final class Config extends \Omnisense\Contracts\Provider\Config
{
    /** @var string */
    public const PREFIX = 'omniticket';

    protected function invalid(string $message): \Throwable
    {
        return new InvalidConfigException($message);
    }
}
