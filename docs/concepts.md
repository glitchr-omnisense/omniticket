---
title: Concepts
order: 20
---

# Concepts

## Provider and its contracts

`Omniticket\ProviderInterface` is one ticketing service, configured:

| Method | Returns |
|---|---|
| `getName(): string` | the factory's name: `weezevent`, `billetweb`, `helloasso`, `eventbrite`, `ticketmaster`, `fixed` |
| `getTitle(): string` | `Weezevent`, `Billetweb`, `HelloAsso`, `Eventbrite`, `Ticketmaster`, `Fixed` |
| `capabilities(): Capabilities` | what it can be asked, its terms and limits; calls nothing |

The contracts, each extending `ProviderInterface`:

| Interface | Method | |
|---|---|---|
| `EventCatalogInterface` | `events(EventQuery $query = new EventQuery()): iterable<Event>` | the account's events (or the public ones), upcoming by default; pages fetched as the iteration goes |
| | `event(string $id): Event` | one event whole: rates, sessions, places left when the provider has them; `NotFoundException` when it has no such event |
| `AttendeeListInterface` | `attendees(string $eventId, ?\DateTimeImmutable $updatedSince = null): iterable<Attendee>` | every ticket of the event, cancelled and refunded ones included; with `$updatedSince`, only those created or changed since (`NotSupportedException` where the provider cannot) |
| `TicketCheckInterface` | `check(string $eventId, string $code): Check` | a code checked: valid, already used, void, unknown. Reading only: the ticket is never marked as scanned |

## Capabilities

`Omniticket\Capabilities` (readonly):

| Property | Type | Default | |
|---|---|---|---|
| `catalog` | bool | `false` | `EventCatalogInterface` |
| `attendees` | bool | `false` | `AttendeeListInterface` |
| `check` | bool | `false` | `TicketCheckInterface` |
| `incremental` | bool | `false` | `attendees()` takes `$updatedSince` |
| `widget` | bool | `false` | its events carry a purchase module to embed (`Event::$widgetUrl`) |
| `terms` | `list<string>` | `[]` | the terms whoever runs it must follow (URLs) |
| `limits` | `list<string>` | `[]` | the rate limits and usage rules the provider states, in words |

`contracts()` lists `catalog`, `attendees`, `check` as it has them; `toArray()` all of it. Each
provider's values are in the [index](index.md#providers) and its own page.

## Factory and Config

`Omniticket\ProviderFactoryInterface`: `getName(): string`, `create(array $options = []):
ProviderInterface`. Every provider extends `Omniticket\ProviderFactory`:

1. `createConfig($options)`: the options in a `Config` (an `ArrayObject`), then the factory's
   `populate()` sets `omniticket.factory_name`, `omniticket.factory_title`,
   `omniticket.required_options` and the defaults of the others;
2. the required options are checked: one missing, `''` or `[]` is an `InvalidConfigException`
   listing every missing key (`The "billetweb" provider needs: key.`);
3. `build($config)` constructs the provider. Nothing is called.

`Config`'s readers: `get($key, $default)`, `string($key)` (`null` when empty), `int()`,
`float()`, `bool()` (a bool, or `"1"`, `"true"`, `"yes"`, `"on"`), `validateNotEmpty($keys)`.

## Registry

`Omniticket\Registry`:

```php
$registry = new Registry([new BilletwebFactory($http), new TicketmasterFactory($http)], [
    'box_office' => ['factory' => 'billetweb', 'options' => ['user' => '...', 'key' => '...']],
    'concerts' => ['factory' => 'ticketmaster', 'options' => ['api_key' => '...']],
]);
```

| Method | Returns |
|---|---|
| `get(string $name): ProviderInterface` | the provider, built the first time and kept |
| `catalog(string $name): EventCatalogInterface` | the provider as a catalogue; `NotSupportedException` when it is not one |
| `attendees(string $name): AttendeeListInterface` | likewise (`The "concerts" provider (Omniticket\Ticketmaster\TicketmasterProvider) does not list attendees.`) |
| `checker(string $name): TicketCheckInterface` | likewise |
| `has()`, `options()`, `names()`, `factories()` | as in the other families |

An unknown name or factory is an `InvalidConfigException` (`No "x" ticketing provider;
configured: ...`, `No "x" factory for the "y" ticketing provider; installed: ...`).

## Event

`Omniticket\Model\Event` (readonly). `events()` fills what a list gives; `event()` the whole
record.

| Property | Type | Default | |
|---|---|---|---|
| `id` | string | - | the provider's id (HelloAsso: the form's slug) |
| `title` | string | - | |
| `start`, `end` | `?\DateTimeImmutable` | `null` | |
| `status` | `EventStatus` | `Unknown` | |
| `url` | `?string` | `null` | where a visitor buys a ticket (the provider's page) |
| `venue` | `?string` | `null` | the place's name and city, joined |
| `availability` | `Availability` | unknown | the whole event's places |
| `rates` | `list<Rate>` | `[]` | |
| `sessions` | `list<Session>` | `[]` | |
| `priceFrom`, `priceTo` | `?Money` | `null` | a price range, when the provider gives one rather than rates |
| `widgetUrl` | `?string` | `null` | the provider's purchase module, to embed in a frame |
| `provider` | string | `''` | the provider's name |

`onSale()`: status `OnSale` and not sold out. `soldOut()`: status `SoldOut` or the availability
sold out. `lowestPrice()`: the lowest rate price, or the range's lower end. `toArray()`.

`EventStatus`: `Draft` (not published), `Upcoming` (published, sales not open), `OnSale`,
`SoldOut`, `SalesClosed` (sales over, the event not cancelled), `Ended`, `Cancelled`,
`Postponed`, `Unknown` (values in snake_case: `on_sale`...).

## EventQuery

`Omniticket\Model\EventQuery` (readonly): `from`, `to` (`?\DateTimeImmutable`), `keyword`
(`?string`), `past` (bool, default `false`), `limit` (`?int`), `now` (`?\DateTimeImmutable`, a
test's clock; the real one when null).

A provider passes what its API can filter on, and returns `$query->filter(...)`, which applies
the whole query to what comes back:

- not `past`: an event whose end (else start) is before now is left out;
- `from`: an event whose end (else start) is before it is left out;
- `to`: an event that starts after it is left out;
- `keyword`: kept when the title or the venue contains it, case-insensitive;
- `limit`: at most that many matching events; the iteration stops there (no further page asked).

An event without dates passes the date tests.

## Rate, Session, Availability

`Rate` (readonly), one price: `id`, `label`, `price` (`?Money`; `null` when the provider gives
none, or the price is the buyer's to choose), `availability`, `salesStart`, `salesEnd`,
`minimum` (`?Money`, the least a buyer may pay when the price is theirs), `category` (`?string`,
Billetweb's category, Weezevent's category path `Categorie 1 > Sous Categorie 1`).

`Session` (readonly), one date of an event sold for several: `id`, `start`, `end`, `name`,
`venue`, `availability`.

`Availability` (readonly), as far as the provider says - an unknown remainder is `null`, not 0:

| | |
|---|---|
| `remaining`, `capacity`, `sold` | `?int` |
| `soldOut`, `unlimited` | bool |
| `Availability::of(?int $remaining, ?int $capacity = null, ?int $sold = null)` | sold out when 0 remains; `remaining` computed as capacity minus sold (never below 0) when not given |
| `Availability::unlimited(?int $sold = null)` | a sale without a limit |
| `known()` | whether the provider said anything: unlimited, sold out, or a remainder |

## Money

`Omniticket\Model\Money` (readonly): `int $amount` in the currency's minor unit - never a float,
so 9.99 stays 999 - and an ISO 4217 `currency`, upper-cased. `fromMajor($major, $currency)` from
units (`"12.50"`, `9.99`, `10`); `major()` back (`"12.50"`, `"3000"` for JPY); `exponent($currency)`
is 0 for BIF, CLP, DJF, GNF, ISK, JPY, KMF, KRW, PYG, RWF, UGX, VND, VUV, XAF, XOF, XPF and 2
otherwise; `isFree()`; `toArray()`.

## Attendee

`Omniticket\Model\Attendee` (readonly), one ticket and the person it is for:

| Property | Type | |
|---|---|---|
| `id` | string | the provider's ticket or participant id |
| `eventId` | string | |
| `code` | `?string` | what the ticket's barcode or QR code holds: what the door checks |
| `firstName`, `lastName`, `email` | `?string` | |
| `rate` | `?string` | the rate's name |
| `status` | `AttendeeStatus` | default `Valid` |
| `checkedInAt` | `?\DateTimeImmutable` | when it was scanned |
| `orderId`, `sessionId`, `ticketUrl` | `?string` | |

`name()` joins the names; `toArray()`. `AttendeeStatus`: `Valid` (paid or free, not scanned),
`CheckedIn` (scanned), `Cancelled`, `Refunded`, `Pending` (not paid yet).

## Check

`Omniticket\Model\Check` (readonly): `code`, `result` (`CheckResult`), `attendee` (`?Attendee`).
`valid()` is `result === Valid`. `CheckResult`: `Valid`, `AlreadyUsed` (scanned), `Void`
(cancelled, refunded or not paid), `Unknown` (no ticket of this event holds the code).

- `Check::of($code, ?Attendee)`: the result from the attendee's status - none `Unknown`, `Valid`
  `Valid`, `CheckedIn` `AlreadyUsed`, `Cancelled`, `Refunded` and `Pending` `Void`.
- `Check::among(iterable $attendees, $code)`: the first attendee whose code equals it (compared
  in constant time) decides: for providers with no lookup by code.

## Support\Data and Http\JsonApi

`Omniticket\Support\Data` reads a decoded answer with types at a dotted path (`venue.city.name`):
`raw()`, `has()`, `string()` (trimmed, `null` when empty; a bool as `"1"`/`"0"`), `int()`,
`float()`, `bool()` (`true`, 1, `"1"`, `"true"`, `"yes"`), `date($path, $timezone)` (a UNIX
timestamp or a string PHP reads, in `$timezone` when it names none; `null` for `0000-00-00...` or
unreadable), `data()`, `list()`, `items()`.

`Omniticket\Http\JsonApi($http, $provider)->request($method, $url, $options, $what): Data` calls
through the injected client and turns every failure into the family's exceptions
([Errors](errors.md)), with the provider's own message when the answer carries one
(`error_description`, `error.message`, `message`, `detail`, `errors.0.message`,
`errors.0.detail`, `fault.faultstring`, `error`).
