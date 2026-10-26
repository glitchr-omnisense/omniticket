---
title: Installation
order: 10
---

# Installation

```sh
composer require omnisense/omniticket omniticket/billetweb symfony/http-client
```

Take the providers the organizer sells on: `omniticket/weezevent`, `omniticket/billetweb`,
`omniticket/helloasso`, `omniticket/eventbrite`, `omniticket/ticketmaster`.

## Requirements

- PHP 8.2 or later.
- `omnisense/omniticket` requires `symfony/http-client-contracts` (^3.0), interfaces only, and
  nothing else.
- Each provider requires `omnisense/omniticket` (1.x) and `symfony/http-client-contracts`
  (^3.0). The application gives the HTTP client - `symfony/http-client`, or any implementation of
  `Symfony\Contracts\HttpClient\HttpClientInterface`, a `MockHttpClient` in a test - to the
  factory's constructor. A factory built without one throws `InvalidConfigException` when the
  provider is built (`The "billetweb" provider needs an HTTP client: give one to
  BilletwebFactory.`).
- `ext-json` (always present since PHP 8.0).

## Plain PHP

A whole script, Billetweb answered from recorded answers by the core's `RecordedHttpClient`:

```php
<?php // bare.php

require __DIR__.'/vendor/autoload.php';

use Omniticket\Billetweb\BilletwebFactory;
use Omniticket\Model\EventQuery;
use Omniticket\Registry;
use Omniticket\Testing\RecordedHttpClient;

// Billetweb's answers, recorded; in production: Symfony\Component\HttpClient\HttpClient::create().
$http = RecordedHttpClient::routes([
    'GET /api/events' => '[{"id":"1235","name":"Retro future shock","start":"2030-12-31 20:00:00","end":"2031-01-01 02:00:00","place":"Paris","shop":"https://www.billetweb.fr/shop.php?event=1235"}]',
    'GET /api/event/1235/avail' => '[{"type":"event","id":"1235","avail":"80","quota":"200","total_sales":"120","status":"available"},{"type":"ticket","id":"124","avail":"80","quota":"100","total_sales":"20"},{"type":"ticket","id":"125","avail":"0","quota":"100","total_sales":"100"}]',
    'GET /api/event/1235/tickets' => '[{"id":"124","name":"Plein tarif","price":"25.00"},{"id":"125","name":"Réduit","price":"18.00"}]',
    'GET /api/event/1235/attendees' => '[{"id":"1","barcode":"4475320","firstname":"Jeanne","name":"Dupont","ticket":"Plein tarif","disabled":"0","used":"0","order_paid":"1"}]',
]);

$registry = new Registry([new BilletwebFactory($http)], [
    'box_office' => ['factory' => 'billetweb', 'options' => ['user' => 'demo', 'key' => 'demo']],
]);

foreach ($registry->catalog('box_office')->events(new EventQuery(limit: 20)) as $event) {
    echo $event->start?->format('Y-m-d H:i'), ' ', $event->title, ', ', $event->venue, "\n";
}

$event = $registry->catalog('box_office')->event('1235');
echo $event->status->value, ', ', $event->availability->remaining, " places left\n";
foreach ($event->rates as $rate) {
    printf("  %s: %s %s, %s left\n", $rate->label, $rate->price?->major(), $rate->price?->currency, $rate->availability->remaining ?? '?');
}

$check = $registry->checker('box_office')->check('1235', '4475320');
echo $check->result->value, ' ', $check->attendee?->name(), "\n";
echo count($http->requests), " calls\n";
```

```
$ php bare.php
2030-12-31 20:00 Retro future shock, Paris
on_sale, 80 places left
  Plein tarif: 25.00 EUR, 80 left
  Réduit: 18.00 EUR, 0 left
valid Jeanne Dupont
5 calls
```

(run on 2026-10-26 in the [harness](harness.md), PHP 8.4)

The pieces:

- a **factory** per provider (`BilletwebFactory`, `WeezeventFactory`...), which takes the HTTP
  client;
- the **registry**, built from the factories and the providers' options, by name; `catalog()`,
  `attendees()` and `checker()` give a provider as each contract, and throw
  `NotSupportedException` for one it lacks (Ticketmaster is a catalogue only) - `Capabilities`
  says it beforehand;
- the **models**: `Event`, `Rate`, `Session`, `Availability`, `Money` (minor units), `Attendee`,
  `Check` ([Concepts](concepts.md)).

Five calls: the events, then `event()`'s three (the event, its availability, its rates), then the
check's one (Billetweb looks a barcode up). Building a provider calls nothing; `events()` calls
when the iteration starts. Every failure is an `Omniticket\Exception\OmniticketException`
([Errors](errors.md)).

No class of a framework is loaded on the way: `Tests/BareTest.php` runs the harness's bare
script in a process of its own and checks that nothing but the package and the HTTP client
contracts was loaded.

## In a framework

There is no Omniticket bundle and no provider in omnibridge/symfony or omnibridge/laravel yet:
build the `Registry` once where the framework builds its services - [Symfony](symfony.md),
[Laravel](laravel.md).
