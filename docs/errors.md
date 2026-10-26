---
title: Errors
order: 50
---

# Errors

Every exception Omniticket throws implements `Omniticket\Exception\OmniticketException` (a
`\Throwable`):

| Exception | Extends | When |
|---|---|---|
| `InvalidConfigException` | `\InvalidArgumentException` | a provider not configured, a factory not installed, a required option missing, no HTTP client given to a factory, Weezevent without a token or a login |
| `NotSupportedException` | `\LogicException` | the registry's typed getter asked of a provider without that contract; an incremental read a provider cannot do (HelloAsso). Before any call |
| `ProviderException` | `\RuntimeException` | the provider answered, and not with what was asked: a status of 400 or more, an answer that is not JSON, no token in a token answer. Its code is the HTTP status |
| `InvalidKeyException` | `ProviderException` | 401 or 403: a wrong or revoked key, a token past its time, a scope not granted |
| `NotFoundException` | `ProviderException` | 404, or an event the provider does not list: nothing under that id |
| `UnreachableException` | `\RuntimeException` | the provider could not be reached: a network error, a timeout |

Messages, as `JsonApi` and the providers write them (run on recorded answers on 2026-10-26):

```
NotSupportedException (0): The "concerts" provider (Omniticket\Ticketmaster\TicketmasterProvider) does not list attendees.
NotFoundException (404): Ticketmaster has no event "9": Resource not found with provided criteria (locale=en-us, id=9)
InvalidKeyException (401): Billetweb refused the credentials (401) for the events: invalid key
NotSupportedException (0): The "helloasso" provider (Omniticket\HelloAsso\HelloAssoProvider) does not read attendees changed since a moment (its items have no such filter).
ProviderException (429): Eventbrite answered 429 for event "1": Hourly rate limit has been reached for this token.
InvalidConfigException (0): The "weezevent" provider needs: access_token, or username and password.
InvalidConfigException (0): The "weezevent" provider needs an HTTP client: give one to WeezeventFactory.
InvalidConfigException (0): The "billetweb" provider needs: key.
ProviderException (200): Example answered 200 for it: an answer that is not JSON
```

The provider's own message is taken from the answer under the names APIs give it
([Concepts](concepts.md#supportdata-and-httpjsonapi)). A 429 (a rate limit) is a
`ProviderException` with code 429. An `events()` listing throws while it is iterated, not when it
is called.

## What to catch where

| Where | Catch |
|---|---|
| a programme page | `UnreachableException`, `ProviderException`: show the last cached list; `InvalidKeyException`: alert someone |
| the door | the same: fall back on the attendee list kept on the application's side (`Check::among()`) |
| an attendee sync | `ProviderException` with code 429: wait, the providers count calls |
| a list over every provider | check `capabilities()` first; `NotSupportedException` is a programming error |
| building the registry, a deploy | `InvalidConfigException`: a configuration error, to fix |
