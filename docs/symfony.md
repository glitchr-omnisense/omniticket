---
title: Symfony
order: 15
---

# Symfony

There is no Omniticket bundle, and omnibridge/symfony has no Omniticket wiring yet: a Symfony
application declares the registry as a service of its own. The factories take the
application's `http_client` by autowiring (`Symfony\Contracts\HttpClient\HttpClientInterface`).

```yaml
# config/services.yaml
services:
    _defaults:
        autowire: true

    Omniticket\Billetweb\BilletwebFactory: ~
    Omniticket\Ticketmaster\TicketmasterFactory: ~

    Omniticket\Registry:
        arguments:
            $factories:
                - '@Omniticket\Billetweb\BilletwebFactory'
                - '@Omniticket\Ticketmaster\TicketmasterFactory'
            $config:
                box_office: { factory: billetweb, options: { user: '%env(BILLETWEB_USER)%', key: '%env(BILLETWEB_KEY)%' } }
                concerts: { factory: ticketmaster, options: { api_key: '%env(TICKETMASTER_API_KEY)%', country_code: FR, city: Strasbourg } }
```

```sh
# .env.local, or bin/console secrets:set
BILLETWEB_USER=...
BILLETWEB_KEY=...
TICKETMASTER_API_KEY=...
```

Then inject the registry:

```php
use Omniticket\Model\EventQuery;
use Omniticket\Registry;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class Programme
{
    public function __construct(private readonly Registry $ticketing, private readonly CacheInterface $cache)
    {
    }

    /** @return list<array<string, mixed>> */
    public function upcoming(): array
    {
        return $this->cache->get('programme', function (ItemInterface $item): array {
            $item->expiresAfter(600);

            return array_map(static fn ($e) => $e->toArray(), [...$this->ticketing->catalog('box_office')->events(new EventQuery(limit: 30))]);
        });
    }
}
```

Nothing is built when the container compiles: a provider is built, and its required options
checked, the first time the registry is asked for it. Each provider states rate limits and fair
use rules (`Capabilities::$limits`): cache the catalogue, keep the attendee list on the
application's side.

This page was written from the classes' constructors; it was not run in a Symfony kernel.
