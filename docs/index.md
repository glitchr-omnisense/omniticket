# omnisense/omniticket

Omniticket reads what a ticketing service sells and who comes, under any PHP host: an
organizer's **events** (dates, status, rates and their prices, sessions, places left, where to
buy and the purchase module to embed), their **attendees** (one per ticket, cancelled and
refunded ones included), and a ticket code **checked** at the door - from Weezevent, Billetweb,
HelloAsso, Eventbrite, or Ticketmaster's public catalogue, through one set of models.

```php
foreach ($registry->catalog('box_office')->events(new EventQuery(limit: 20)) as $event) {
    echo $event->start?->format('d/m H:i'), ' ', $event->title, $event->soldOut() ? ' (complet)' : '', "\n";
}
$event = $registry->catalog('box_office')->event('1235');                // rates, sessions, places left
$registry->attendees('box_office')->attendees('1235', $lastRead);        // what changed since
$registry->checker('box_office')->check('1235', $scannedCode)->result;   // CheckResult::Valid, AlreadyUsed...
```

Each provider implements the **contracts** it can answer - `EventCatalogInterface`,
`AttendeeListInterface`, `TicketCheckInterface` - and says so, with the terms and rate limits it
must keep, in its `Capabilities`. An `EventQuery` (a period, a keyword, past events or not, a
limit) is applied by the core to what every provider returns, so one query means the same
everywhere. The design is the Omnisense families' one: a provider's **factory** builds it from
options, a **registry** holds the application's providers by name. The core requires
`symfony/http-client-contracts` (interfaces only) and nothing else; the HTTP client is the
application's.

Published as `omnisense/omniticket`, formerly `omniscient/omniticket`: the package `replace`s
the old name. The namespace is `Omniticket\`; providers keep the `omniticket/` vendor and the
`Omniticket\<Provider>\` namespace.

## When to use it

- A venue's, a festival's or an association's site that shows its events, prices and places left
  from the ticketing service it sells on, and links to (or embeds) the purchase page.
- A back office that keeps the attendee list on its side, read incrementally.
- A door check against the service's own data, from the site's tools.

It is not a box office (a ticket the site sells itself is a product of the marketplace), not a
scanning application - **a check only reads**: no ticket is marked as scanned at the provider;
the provider's own application does that - and not an agenda (a link to the ticket page is
enough there). It writes nothing to any provider and keeps nothing.

## Providers

| Package | Factory name | Catalogue | Attendees | Check | Incremental | Widget | Credentials |
|---|---|---|---|---|---|---|---|
| [`omniticket/weezevent`](https://github.com/glitchr-omnisense/omniticket-weezevent) | `weezevent` | yes | yes | among the participants | yes | - | a partner API key and the organizer's token (or login) |
| [`omniticket/billetweb`](https://github.com/glitchr-omnisense/omniticket-billetweb) | `billetweb` | yes | yes | lookup by barcode | yes | yes | an API user and key |
| [`omniticket/helloasso`](https://github.com/glitchr-omnisense/omniticket-helloasso) | `helloasso` | yes (forms of type Event) | yes | among the items, never "already used" | - | yes | an OAuth2 client |
| [`omniticket/eventbrite`](https://github.com/glitchr-omnisense/omniticket-eventbrite) | `eventbrite` | yes | yes | among the attendees | yes | - | a private or OAuth token |
| [`omniticket/ticketmaster`](https://github.com/glitchr-omnisense/omniticket-ticketmaster) | `ticketmaster` | yes (public, Discovery API) | - | - | - | - | an app's consumer key |
| `Omniticket\Testing\FixedTicketingFactory` (in the core) | `fixed` | yes | yes | among the attendees | - | - | none |

The columns are each provider's `capabilities()`, probed in the [harness](harness.md) on
2026-10-26. No provider was run against a live account; Weezevent's one public endpoint was
([Verified, and not](verified.md)).

## How this manual is organised

- [Installation](installation.md): requirements, a first script in plain PHP.
- [Symfony](symfony.md) and [Laravel](laravel.md): no bridge yet; the registry as a service, by
  hand.
- [Concepts](concepts.md): providers and their contracts, capabilities, factories, the registry,
  every model.
- Usage: [events](usage-events.md) and [attendees and checks](usage-attendees.md).
- [Configuration reference](configuration.md): every provider's options.
- [Errors](errors.md).
- [Testing](testing.md): the fixed provider, the recorded HTTP client, the load check.
- [The Docker harness](harness.md).
- [Writing a provider](providers.md).
- [Verified, and not](verified.md).
