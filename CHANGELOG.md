# Changelog

## [Unreleased]

### Changed

- `Config::guard()` is the kernel's: omniticket's override (the same guard, without the "plan"
  option) is gone, as omnisense/contracts' round 3 allowed. The kernel's runs the policy under the
  "plan" option and tightened by "policy", in the registry's limiter, and throws the Backoff's
  exception, `UsageLimitReachedException` (the factory's `limitException()`). It is in
  omnisense/contracts since 16c8da1 (1568f2c, what the sites' vendors hold, has it).
  Tests/PolicyTest expects "1000 an hour", the kernel's wording since 16c8da1.

- omnisense/omniticket builds on omnisense/contracts, the kernel every omni family shares.
  `Config`, `ProviderInterface`, `ProviderFactoryInterface`, `ProviderFactory` (`populate()` and
  `build()` as before), `Registry`, `Http\Redaction`, `Testing\RecordedHttpClient` (its routes
  as before, a route's headers besides) and `RecordedResponse` keep their names and their API,
  each a thin subclass of the kernel's; `OmniticketException` extends the kernel's
  `ExceptionInterface`, `InvalidConfigException`, `NotSupportedException` and
  `UnreachableException` its classes. `Http\JsonApi` keeps its API and its exceptions and makes
  its calls through the kernel's `JsonClient`. `Support\Data` (its readers differ from the
  kernel's `Data`), `ProviderException` and `Testing\LoadCheck` stay the family's.
- `Registry::catalog()`, `attendees()` and `checker()` give the provider behind its guard
  (`Policy\GuardedCatalog`, `GuardedAttendees`, `GuardedChecker`; `inner()` gives the provider),
  no longer the provider itself; `get()` still does.

### Added

- Usage policies ([docs/policies.md](docs/policies.md)): `Capabilities::policy()`, given
  (`policy:`) or made of `$terms` and `$limits`, which stay, deprecated. Each guarded call is
  checked against the provider's rate limits and counted; over one, or while the provider asks
  to wait (a 429 and its `Retry-After`, a `RateLimit` field with nothing left), it is never made:
  `Exception\UsageLimitReachedException`. A site tightens a policy (`policy`), says what it is
  (`usage`); `Registry::policy()`, `policies()`, `guard()`, `names()` by contract, a shared
  `RateLimiterInterface` as the registry's third argument. `JsonApi` takes the provider's
  `Backoff`; `Config::guard()` counts a request a provider makes inside an operation (a token).
- The harness mounts omnisense/contracts' checkout (`OMNISENSE_CONTRACTS`) and runs its tests,
  and names its entrypoint (the image may be another family's harness tagged).

### Fixed

- `Testing\FixedTicketing` and its factory take attendees by any event id key (an id such as
  "1" is an integer key in PHP): PHPStan's error in `RegistryTest` gone.

### Fixed

- No exception `JsonApi::request()` throws keeps its trace's arguments: an error answered (a refused key,
  nothing there, a server error, a body that is not JSON) kept the request's options - the query,
  headers and token with the key - in its trace (`zend.exception_ignore_args` off), as the
  `UnreachableException` did before its fix.
- An `UnreachableException` no longer carries the request's secrets. `Http\JsonApi::request()` put
  the HTTP client's message, the whole URL with a key in its query (Ticketmaster's `apikey=`), in
  its message, wrapped the client's own exception as its previous, and every trace of the chain
  kept the arguments of the calls under it (the query and headers with the key, when
  `zend.exception_ignore_args` is off). The message and the previous - now a copy of the client's
  exception, the same class and code - have the key's parameters, the URL's user part and the
  request's secrets masked as `***` (`Http\Redaction`), and no trace of the chain keeps its
  arguments.
- `FixedTicketing::attendees()` with `$updatedSince` is a `NotSupportedException`, as the contract asks
  of a provider that is not incremental (its `capabilities()->incremental` is false), where it
  ignored the date and returned every attendee.
