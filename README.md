# omnisense/omniticket

Tickets sold elsewhere, read and checked under any PHP host. A site that shows where a box office
stands or welcomes its guests at the door asks the same three questions whoever sells the
tickets: which events, at which dates and prices, with how many places left and where to buy;
who comes; is this ticket code valid. Plain PHP: no framework in `require`, the application's
HTTP client injected (Symfony's HTTP client contracts).

```php
use Omniticket\Billetweb\BilletwebFactory;
use Omniticket\Model\EventQuery;
use Omniticket\Registry;
use Symfony\Component\HttpClient\HttpClient;

$registry = new Registry([new BilletwebFactory(HttpClient::create())], [
    'box_office' => ['factory' => 'billetweb', 'options' => ['user' => '...', 'key' => '...']],
]);

foreach ($registry->catalog('box_office')->events(new EventQuery(limit: 10)) as $event) {
    echo $event->title, ' ', $event->start?->format('d/m H:i'), ' ', $event->soldOut() ? 'complet' : $event->url, "\n";
}
$event = $registry->catalog('box_office')->event('1235');          // rates, sessions, places left
$check = $registry->checker('box_office')->check('1235', $scanned); // valid, already_used, void, unknown
```

## The shape

- **Models** (`Omniticket\Model`): `Event` (dates, status, purchase link and embeddable module,
  venue, `Availability`, `Rate`s, `Session`s, price range), `Rate`, `Session`, `Money` (minor
  units and ISO 4217 code, never a float), `Availability` (remaining, capacity, sold, sold out,
  unlimited - unknown is null, not zero), `Attendee`, `Check` and the enums `EventStatus`,
  `AttendeeStatus`, `CheckResult`. `EventQuery` (period, keyword, past or not, limit) is applied by
  the core to what every provider returns, so one query means the same everywhere.
- **Contracts by capability**, each provider saying which it has (`Capabilities`, with the terms
  and rate limits it must keep): `EventCatalogInterface` (events, one event whole),
  `AttendeeListInterface` (one `Attendee` per ticket, incrementally where the provider allows),
  `TicketCheckInterface` (a code checked - reading only: the ticket is never marked as scanned at
  the provider). `Registry`, `Config`, `ProviderFactory`: providers by name, as in the other omni
  families.
- **HTTP**: `Http\JsonApi` calls a JSON API through the injected client and turns failures into
  the family's exceptions (`UnreachableException`, `InvalidKeyException` on 401/403,
  `NotFoundException` on 404, `ProviderException`); `Support\Data` reads answers with types.
- **Testing**: `Testing\FixedTicketing` (every contract from given events and attendees: an
  application's tests, a demo), `Testing\RecordedHttpClient` (answers by route, requests kept),
  `Testing\LoadCheck` (the bare check every package of the family runs).
- **Providers** are packages:

| Package | Contracts | Notes |
|---|---|---|
| [omniticket/weezevent](https://github.com/glitchr-omnisense/omniticket-weezevent) | catalog, attendees, check | partner API key + organizer token; incremental |
| [omniticket/billetweb](https://github.com/glitchr-omnisense/omniticket-billetweb) | catalog, attendees, check | read-only key; lookup by barcode; incremental; 10 attendee calls a minute |
| [omniticket/helloasso](https://github.com/glitchr-omnisense/omniticket-helloasso) | catalog, attendees, check | OAuth2 client credentials; an event is a form of type Event |
| [omniticket/eventbrite](https://github.com/glitchr-omnisense/omniticket-eventbrite) | catalog, attendees, check | private or OAuth token; incremental |
| [omniticket/ticketmaster](https://github.com/glitchr-omnisense/omniticket-ticketmaster) | catalog | Discovery API: public events, read-only |

- **Hosts**: no bridge yet. omnibridge/symfony and omnibridge/laravel wire the family when the
  first site needs it; nothing of a framework here.

What it is not: a box office (a ticket the site sells itself is a product of the marketplace), a
scanning application (the provider's own marks tickets as scanned), an agenda
(`agenda\Event.ticketsUrl` stays a link where that is enough).

## Documentation

- [Installation, in plain PHP](docs/installation.md)
- [Writing a provider](docs/providers.md)
- [The harness](docs/harness.md)

## Verified, and not

- Verified (`docker compose run --rm omniticket test` in `docker/`, PHP 8.4, 2026-10-24): the
  models (money in minor units, availability, statuses, checks), the query's filter, the
  registry and its errors, `JsonApi`'s mapping of statuses to exceptions; in plain PHP, events
  listed, read, attendees and codes checked through a Registry and a recorded JSON API, loading
  nothing but omniticket and Symfony's HTTP client contracts (`BareTest`); PHPStan at the highest
  level, no error baselined, on the core and the five providers.
- Verified live (`docker compose run --rm omniticket live`, 2026-10-24): Weezevent's public
  endpoint (`GET /event/categories`, no key) - its host and its JSON.
- Not verified live: every call that needs an account or a key (all five providers' events,
  attendees and checks). Billetweb's and Weezevent's tests answer with the examples of their own
  documentation; HelloAsso's, Eventbrite's and Ticketmaster's with answers composed from their
  documented fields. Each provider's README says which.

## Responsibility

omnisense/omniticket and its omniticket/* providers are unofficial clients, affiliated with none
of the services they reach. The MIT licence covers the code only and grants no right to any
service or its data - attendees' names and emails are personal data, whose processing is the
application's responsibility. Whoever runs them complies with each service's terms (named in each
provider's README and in `Capabilities::$terms`) and obtains what they require.

## Licence

MIT.
