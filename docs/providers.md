# Writing a provider

A provider is a package `omniticket/<name>` with a factory and a provider class:

- the factory extends `Omniticket\ProviderFactory`: `populate()` names it, lists its required
  options and their defaults; `build()` returns the provider. The HTTP client comes through the
  factory's constructor, never created inside.
- the provider implements the contracts it can answer - `EventCatalogInterface`,
  `AttendeeListInterface`, `TicketCheckInterface` - and says so in `capabilities()`, with the
  terms and the rate limits the service states.
- calls go through `Http\JsonApi` (failures become the family's exceptions) and answers are read
  with `Support\Data`; a listing passes what the API can filter on and returns
  `$query->filter(...)`, so the core applies the whole query.
- a check reads: it never marks a ticket as scanned. Without a lookup by code, `Check::among()`
  over the attendees.
- an `Attendee` per ticket, cancelled and refunded ones included (their status says so); money in
  minor units (`Money::fromMajor()` when the API gives units); an unknown remainder is null.
- tests run in the core's harness (`docker/harness/plugins.php` lists the providers) with recorded
  answers - from the service's documentation when it publishes examples, said so in the test - and
  a `BareTest` (`Testing\LoadCheck`); a `LiveTest` (group `live`) only for what needs no account.
