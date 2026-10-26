---
title: Verified, and not
order: 90
---

# Verified, and not

## What the tests prove

Run on 2026-10-26 in the [harness](harness.md), every package from its checkout, PHP 8.4, the
`live` group left out, no network: 50 tests, 200 assertions, green.

- The core: the models (money in minor units, availability, statuses, checks), the query's
  filter, the registry and its errors, `JsonApi`'s mapping of statuses to exceptions; in plain
  PHP, events listed, read, attendees and codes checked through a registry and a recorded JSON
  API, loading nothing but omniticket and Symfony's HTTP client contracts (`BareTest`).
- Each provider, every class loading with its requirements alone (`BareTest`, `LoadCheck`), and:
  Billetweb's and Weezevent's calls against the examples of their own documentation, as
  published; HelloAsso's, Eventbrite's and Ticketmaster's against answers composed from their
  documented fields, not recorded - each provider's README says which. Events and their
  statuses, one event whole (rates, sessions, places left), attendees (incrementally where the
  provider allows), checks valid, already used, refunded and unknown, a refused key.

The examples of this manual (the installation script on Billetweb, the error messages, the
testing page, the bare script) were run in the harness on 2026-10-26.

## What was run live

Per the READMEs, on 2026-10-24 (`docker compose run --rm omniticket live`): Weezevent's public
endpoint, `GET /event/categories`, no key - its host and its JSON. Not run again for this manual.

## What is not verified

- Every call that needs an account or a key: all five providers' events, attendees and checks.
- Whether Weezevent cuts a very large participant list without paging (`max` and `page` are
  documented without their base).
- Eventbrite's response shapes were not re-read on 2026-10-24, nor its rate figures.
- The [Symfony](symfony.md) and [Laravel](laravel.md) pages were written from the classes'
  constructors, not run in an application.

## Responsibility

omnisense/omniticket and its omniticket/* providers are unofficial clients, affiliated with none
of the services they reach. The MIT licence covers the code only and grants no right to any
service or its data; whoever runs them complies with each service's terms and limits (named in
each provider's README and in `Capabilities::$terms` and `$limits`). Attendees' names and e-mails
are personal data: keep and show them as the organizer's own rules and the law allow.
