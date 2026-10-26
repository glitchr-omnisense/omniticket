---
title: Testing
order: 60
---

# Testing

## The fixed provider

`Omniticket\Testing\FixedTicketing` answers from what it was given, every contract, no network:
an application's tests, a demo, the harness's bare script. `FixedTicketingFactory` gives it to a
registry under the name `fixed`:

```php
use Omniticket\Model\Attendee;
use Omniticket\Model\AttendeeStatus;
use Omniticket\Model\Availability;
use Omniticket\Model\Event;
use Omniticket\Model\EventQuery;
use Omniticket\Model\EventStatus;
use Omniticket\Model\Money;
use Omniticket\Model\Rate;
use Omniticket\Registry;
use Omniticket\Testing\FixedTicketingFactory;

$events = [
    new Event('1235', 'Retro future shock', new \DateTimeImmutable('2026-12-31 20:00:00'), status: EventStatus::OnSale,
        availability: Availability::of(null, 200, 120),
        rates: [new Rate('124', 'Plein tarif', Money::fromMajor('25.00', 'EUR'), Availability::of(90, 100))]),
];
$attendees = ['1235' => [
    new Attendee('1', '1235', '4475320', 'Jeanne', 'Dupont'),
    new Attendee('2', '1235', '4475321', 'Paul', 'Martin', status: AttendeeStatus::CheckedIn),
]];

$registry = new Registry([new FixedTicketingFactory($events, $attendees)], ['box_office' => ['factory' => 'fixed']]);

$registry->catalog('box_office')->events(new EventQuery(now: new \DateTimeImmutable('2026-11-01')));   // the event
$registry->checker('box_office')->check('1235', '4475321')->result;                                     // CheckResult::AlreadyUsed
```

`events()` applies the query to the events given (give `now` for a fixed clock); `event()`
throws `NotFoundException` for an id not given; `check()` is `Check::among()` over the
attendees. The application keeps the name it uses; only the factory changes.

> [!WARNING]
> Known defect: `FixedTicketing` says it is not incremental (`capabilities()->incremental` is
> false) but `attendees($eventId, $updatedSince)` ignores the date and returns every attendee,
> where the contract asks for a `NotSupportedException`.

## The recorded HTTP client

`Omniticket\Testing\RecordedHttpClient` implements Symfony's `HttpClientInterface`, answers from
what it was given and keeps every request (`$requests`: method, URL, options): a provider's
calls checked with no network.

```php
use Omniticket\Testing\RecordedHttpClient;

$http = RecordedHttpClient::routes([
    'GET /api/events' => '[{"id":"1235","name":"Retro future shock","start":"2030-12-31 20:00:00"}]',
    'GET /api/private' => [401, '{"error":"invalid key"}'],
]);
```

`routes()`: the first route whose method matches and whose path ends the URL's path (the query is
ignored) answers its body (status 200), or `[status, body]`; anything not recorded is a 404
(`{"error":"not recorded: GET /path"}`). `new RecordedHttpClient(fn ($method, $url, $options) =>
[status, body, headers])` answers anything. It does not stream. The
[installation](installation.md#plain-php) script runs Billetweb on it.

## The load check

`Omniticket\Testing\LoadCheck::run($root, $namespace, $autoload)` loads every class of a package
in a PHP process of its own (`bin/load-all`) and reports what came from outside: classes of a
framework, an HTTP client or an application (`LoadCheck::FOREIGN`; Symfony's HTTP client and
service contracts are allowed), and files of packages it does not require (`outside()`). Every
package of the family runs it in its `BareTest`.

## Live checks

`docker compose run --rm omniticket live` runs the `live` group: only what needs no account -
Weezevent's `GET /event/categories` (`WeezeventProvider::eventCategories()`).
