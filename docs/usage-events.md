---
title: Events
order: 31
---

# Events

```php
use Omniticket\Model\EventQuery;

$catalog = $registry->catalog('box_office');

$catalog->events();                                                     // upcoming ones
$catalog->events(new EventQuery(from: new \DateTimeImmutable('first day of next month'), limit: 20));
$catalog->events(new EventQuery(keyword: 'jazz', past: true));

$event = $catalog->event('1235');
foreach ($event->rates as $rate) {
    printf("%s: %s %s, %s left\n", $rate->label, $rate->price?->major() ?? 'free choice', $rate->price?->currency, $rate->availability->remaining ?? '?');
}
echo $event->onSale() ? 'Buy: '.$event->url : $event->status->value;
```

`events()` returns an iterable: a provider's calls are made when the iteration starts, and with a
`limit` no page is asked beyond what the limit needs. A failure (a refused key...) is thrown
during the iteration. Keep `[...$events]` or `toArray()` in a cache: every provider limits its
calls.

## What each provider reads

| | Events listed | `event()` | Status | Places left | Widget |
|---|---|---|---|---|---|
| Weezevent | `GET /events` (`include_closed` with `past`, `include_without_sales`) | `/event/{id}/details`, `/tickets` (rates in nested categories), `/dates` (sessions): three calls | listed: sales status 1 on sale, 2 upcoming, else ended or unknown by date; whole: ended, upcoming, sales closed by the sale period, else on sale | per rate from its quota (0: no quota) and participants; the event's sold count | - |
| Billetweb | `GET /api/events` (`past=1` with `past`) | the event, `/avail`, `/tickets`: three calls | ended by date, sold out, on sale when Billetweb says `available`, else unknown (a listed event is unknown or ended: the list has no availability) | per event, rate and session; -1 is no limit | `shop`, the purchase page Billetweb's embedding code frames |
| HelloAsso | `GET /v5/organizations/{slug}/forms?formTypes=Event`, pages of 100 | `.../forms/Event/{formSlug}/public` | `Draft` draft; `Disabled`, `Deleted` sales closed; ended, upcoming, sales closed by dates; `Public` on sale | not given | `widgetFullUrl` |
| Eventbrite | `GET /organizations/{id}/events/` (`time_filter` `current_future`, or `all` with `past`; `order_by=start_asc`; ticket availability and venue expanded), by continuation | `/events/{id}/`, `/events/{id}/ticket_classes/` | `draft`, `canceled`, `ended`/`completed`; `live`/`started`: sold out, upcoming before sales start, sales closed when no ticket is available, else on sale | per ticket class from quantities; sold out from Eventbrite's flag | - |
| Ticketmaster | `GET /events.json` (the configured filters, `startDateTime` from `from` or now, `endDateTime`, `keyword`, `sort=date,asc`, 50 a page, never past the 1000th result) | `/events/{id}.json` | `onsale` (upcoming before the public sale), `offsale` sales closed, `cancelled`, `postponed`, `rescheduled` on sale | not given; a price range | - |

Prices: Billetweb and Weezevent give units in a currency they do not name (the `currency`
option); HelloAsso cents in EUR (a price of 0 with a `minAmount` is the buyer's to choose: no
price, a minimum); Eventbrite currency objects in minor units (a free class is 0); Ticketmaster
a price range in units.

## Showing a programme

```php
use Omniticket\Model\EventQuery;

$events = [];
foreach ($registry->names() as $name) {
    if ($registry->get($name)->capabilities()->catalog) {
        array_push($events, ...$registry->catalog($name)->events(new EventQuery(limit: 50)));
    }
}
usort($events, static fn ($a, $b) => $a->start <=> $b->start);

foreach ($events as $event) {
    printf("%s  %-40s %s %s\n", $event->start?->format('d/m H:i'), $event->title, $event->lowestPrice()?->major() ?? '', $event->soldOut() ? 'complet' : '');
}
```

`Event::$widgetUrl` (Billetweb, HelloAsso) is the provider's purchase module, to put in a frame;
`Event::$url` its page. Ticketmaster's terms ask that purchases go through the event's `url`.
