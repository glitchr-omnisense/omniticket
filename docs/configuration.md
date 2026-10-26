---
title: Configuration reference
order: 40
---

# Configuration reference

## The registry

```php
new Registry(iterable $factories, array $config);
```

| Key | Type | Required | |
|---|---|---|---|
| `<name>` | string | - | the name the application gives the provider: `box_office`, `concerts`... |
| `<name>.factory` | string | yes | `weezevent`, `billetweb`, `helloasso`, `eventbrite`, `ticketmaster`, `fixed` |
| `<name>.options` | array | no (`[]`) | the provider's options, below |

Options are taken as given: a required option given as `null`, `''` or `[]` is reported missing;
an optional one given as `''` reads as not set. Every provider's factory takes the HTTP client
in its constructor (`?HttpClientInterface $http = null`); without one, building the provider is
an `InvalidConfigException`.

## weezevent

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `api_key` | string | - | yes | the partner API key Weezevent gives (access is limited to registered partners), sent as `api_key` |
| `access_token` | string | none | this, or `username` and `password` | the organizer's token, persistent: `POST /auth/access_token` once and keep it |
| `username`, `password` | string | none | (see above) | the organizer's login, exchanged for a token at the first call and kept for the provider's life |
| `timezone` | string | `Europe/Paris` | no | the zone Weezevent's dates are in: it gives local times without one |
| `currency` | string | `EUR` | no | the currency of its prices: its answers do not name it |
| `url` | string | `https://api.weezevent.com` | no | |

Neither a token nor a login: `InvalidConfigException` (`The "weezevent" provider needs:
access_token, or username and password.`).

## billetweb

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `user` | string | - | yes | the API user (Billetweb's back office, "API") |
| `key` | string | - | yes | the API key; sent with the user as HTTP Basic. A read-only key is all this package needs |
| `timezone` | string | `Europe/Paris` | no | the zone Billetweb's dates are in |
| `currency` | string | `EUR` | no | the currency of its prices |
| `url` | string | `https://www.billetweb.fr` | no | |

## helloasso

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `client_id`, `client_secret` | string | - | yes | the API client of the association's back office ("Intégrations et API"): a token from `POST /oauth2/token`, client credentials, kept until a minute before it expires (30 minutes) |
| `organization` | string | - | yes | the association's slug, as in `https://www.helloasso.com/associations/<slug>` |
| `url` | string | `https://api.helloasso.com` | no | the sandbox: `https://api.helloasso-sandbox.com` |

Prices are read in EUR.

## eventbrite

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `token` | string | - | yes | a private token (account settings, "Developer links", "API keys") or an OAuth token, sent as a bearer |
| `organization` | string | - | yes | the organization's id, whose events are listed (`GET /users/me/organizations/` gives it) |
| `url` | string | `https://www.eventbriteapi.com/v3` | no | |

## ticketmaster

| Option | Type | Default | Required | |
|---|---|---|---|---|
| `api_key` | string | - | yes | the consumer key of an app on developer.ticketmaster.com, sent as `apikey` |
| `country_code` | string | none | no | ISO 3166-1 alpha-2: every search narrowed to it (`countryCode`) |
| `city` | string | none | no | `city` |
| `venue_id` | string | none | no | `venueId`: a venue's events |
| `attraction_id` | string | none | no | `attractionId`: an artist's events |
| `classification_name` | string | none | no | `classificationName`: music, sports... |
| `locale` | string | none | no | `locale` |
| `url` | string | `https://app.ticketmaster.com/discovery/v2` | no | |

## fixed

`Omniticket\Testing\FixedTicketingFactory(array $events = [], array $attendees = [])`: no option;
the events and the attendees (by event id) are given to the constructor ([Testing](testing.md)).
