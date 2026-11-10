<?php

namespace Omniticket;

use Omnisense\Contracts\Policy\InMemoryRateLimiter;
use Omnisense\Contracts\Policy\PolicyGuard;
use Omnisense\Contracts\Policy\RateLimiterInterface;
use Omnisense\Contracts\Policy\UsagePolicy;
use Omniticket\Exception\InvalidConfigException;
use Omniticket\Exception\UsageLimitReachedException;

/**
 * A provider's configuration while its factory builds it: the options
 * given, the factory's defaults under them, and the keys that must be
 * filled - omnisense/contracts' Config, with omniticket's prefix
 * ("omniticket.factory_name", "omniticket.backoff"...) and its
 * InvalidConfigException. Every provider takes two options besides its own:
 * "policy" (the site's tightening of its usage policy) and "usage" (what
 * the site says of itself: commercial, open_source, contract).
 */
final class Config extends \Omnisense\Contracts\Provider\Config
{
    /** @var string */
    public const PREFIX = 'omniticket';

    /**
     * A guard for the requests a provider makes inside its operations, which
     * the registry's guard does not see (an access token renewed in the
     * middle of a listing): its declared policy tightened by the "policy"
     * option, counted in the registry's limiter (an in-memory one outside a
     * registry), waiting with the provider's Backoff. The provider wraps
     * such a request in call() under an operation of its own ("token").
     */
    public function guard(UsagePolicy $policy): PolicyGuard
    {
        $limiter = $this->get($this->key('limiter'));

        return new PolicyGuard($policy->tighten($this->map('policy')), $limiter instanceof RateLimiterInterface ? $limiter : new InMemoryRateLimiter(), $this->backoff(), UsageLimitReachedException::class);
    }

    protected function invalid(string $message): \Throwable
    {
        return new InvalidConfigException($message);
    }
}
