# Usage policies

Every provider declares its usage policy as data: `policy.json` beside its classes, named in its
`composer.json` (`extra.omnisense.policy`), in omnisense/contracts' schema
(`resources/usage-policy.schema.json`): its rate limits, the rights its terms give (commercial
use, caching, storage, redistribution, attribution), what every request needs, and where and
when it was read (`provenance.verifiedAt`). What the terms do not say is `unknown` (or `null` for
the limits): nothing is guessed.

## What each provider declares (read on 2026-11-10)

| Provider | Rate limits | Rights | Source |
|---|---|---|---|
| billetweb | attendee functions (`attendees`, `check`): 10 a minute per key; the rest fair use | storage allowed (asked: keep the data, read incrementally); the rest unknown | [api.php](https://www.billetweb.fr/bo/api.php) |
| eventbrite | 1,000 an hour per OAuth token (terms 3.5), 2,000 an hour and 48,000 a day per application (rate limits page) | commercial unknown (no direct gain, indirect allowed: see notes), storage forbidden (only future events), redistribution of the API forbidden, the event's title and a direct link shown | [API Terms of Use](https://www.eventbrite.com/support/articleredirect?anum=8483), [rate limits](https://www.eventbrite.com/platform/docs/rate-limits) |
| helloasso | authentication API (`token`): 10 every 10 seconds, 20 every 10 minutes, 50 an hour; the other endpoints unknown | unknown (the general terms answered 403) | [limitation-api](https://dev.helloasso.com/docs/limitation-api) |
| ticketmaster | 5,000 a day and 5 a second per key | commercial forbidden, caching allowed for reasonable periods, storage beyond it and redistribution forbidden | [Getting started](https://developer.ticketmaster.com/products-and-docs/apis/getting-started/), [Terms of Use](https://developer.ticketmaster.com/support/terms-of-use/) |
| weezevent | 600 requests a minute; credentials (`token`): 10 a minute | storage allowed (asked); the rest unknown | [fair use](https://docapi.weezevent.com/#/legacy/fair-use) |

Each `policy.json`'s `provenance.notes` says what the fields could not: Eventbrite's split of
commercial use, Ticketmaster's "reasonable periods", Weezevent's bans after failed logins.

## The guard

`Registry::catalog()`, `attendees()` and `checker()` give the provider behind its guard
(omnisense/contracts' `PolicyGuard`): each call is an operation named after its method -
`events`, `event`, `attendees`, `check` - checked against the limits that count it before it is
made, then counted (failed or not). Over a limit, or while the provider asked to wait, the call
is never made: `Exception\UsageLimitReachedException` (an `OmniticketException`), with
`retryAfter` in seconds when it is known and the `limit` reached. `get()` gives the provider
itself, unguarded.

A listing (`events()`, `attendees()`) is one call, checked before its first page; the further
pages it fetches as the iteration goes are not counted by the guard. The provider's `Backoff`
watches every request: a 429 and its `Retry-After`, or a `RateLimit` field with nothing left,
hold every call of the provider until the wait is over.

The requests a provider makes inside an operation - HelloAsso's and Weezevent's access token -
are counted under an operation of their own, `token`, by a guard the factory builds
(`Config::guard()`), in the same limiter.

## A site's tightening

```php
$registry = new Registry([new TicketmasterFactory($http)], [
    'concerts' => [
        'factory' => 'ticketmaster',
        'options' => ['api_key' => '...'],
        'policy' => ['rateLimits' => [['max' => 1000, 'window' => 'day']]], // never looser
        'usage' => ['commercial' => false],                                  // what the site is
    ],
], $limiter);

$registry->policy('concerts');   // Ticketmaster's, tightened
$registry->policies();           // every configured provider's: a credits page
```

A site's limits are added to the declared ones (each counts); a limit counting the same calls with
a higher maximum, a permission made looser, a longer time to live are refused
(`InvalidPolicyException`). A `usage` the terms forbid fails at once (`UsageNotPermitted`): a
commercial site on Ticketmaster. An application served by many processes passes a shared
`RateLimiterInterface` as the registry's third argument (omnibridge/symfony's, over
symfony/rate-limiter).

## The older fields

`Capabilities::$terms` and `$limits` (words) stay, deprecated: given alone, they make a policy
whose limits are unknown (the words kept in its notes, never read as figures); given a policy,
they are read from it.
