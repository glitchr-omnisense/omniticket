---
title: Writing a provider
order: 80
---

# Writing a provider

A provider is a package `omniticket/<name>` with a factory and a provider class:

- the factory extends `Omniticket\ProviderFactory`: `populate()` names it, lists its required
  options and their defaults; `build()` returns the provider. The HTTP client comes through the
  factory's constructor, never created inside.
- the provider implements the contracts it can answer - `EventCatalogInterface`,
  `AttendeeListInterface`, `TicketCheckInterface` - and says so in `capabilities()`, with the
  terms and the rate limits the service states.
- calls go through `Http\JsonApi` (failures become the family's exceptions) and answers are read
  with `Support\Data`; a listing passes what the API can filter on and returns
  `$query->filter(...)`, so the core applies the whole query.
- a check reads: it never marks a ticket as scanned. Without a lookup by code, `Check::among()`
  over the attendees.
- an `Attendee` per ticket, cancelled and refunded ones included (their status says so); money in
  minor units (`Money::fromMajor()` when the API gives units); an unknown remainder is null.
- tests run in the core's harness (`docker/harness/plugins.php` lists the providers) with recorded
  answers - from the service's documentation when it publishes examples, said so in the test - and
  a `BareTest` (`Testing\LoadCheck`); a `LiveTest` (group `live`) only for what needs no account.

## A skeleton

```php
namespace Omniticket\Example;

use Omniticket\Capabilities;
use Omniticket\Config;
use Omniticket\EventCatalogInterface;
use Omniticket\Exception\InvalidConfigException;
use Omniticket\Http\JsonApi;
use Omniticket\Model\Event;
use Omniticket\Model\EventQuery;
use Omniticket\ProviderFactory;
use Omniticket\ProviderInterface;
use Omniticket\Support\Data;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ExampleFactory extends ProviderFactory
{
    public function __construct(private readonly ?HttpClientInterface $http = null)
    {
    }

    protected function populate(Config $c): void
    {
        $c->defaults(['omniticket.factory_name' => 'example', 'omniticket.factory_title' => 'Example', 'omniticket.required_options' => ['api_key']]);
    }

    protected function build(Config $c): ProviderInterface
    {
        return new ExampleProvider($this->http ?? throw new InvalidConfigException('The "example" provider needs an HTTP client.'), (string) $c->string('api_key'));
    }
}

final class ExampleProvider implements EventCatalogInterface
{
    private readonly JsonApi $api;

    public function __construct(HttpClientInterface $http, #[\SensitiveParameter] private readonly string $apiKey)
    {
        $this->api = new JsonApi($http, 'Example');
    }

    public function getName(): string { return 'example'; }

    public function getTitle(): string { return 'Example'; }

    public function capabilities(): Capabilities
    {
        return new Capabilities(catalog: true, terms: ['https://example.org/terms'], limits: ['what the service states']);
    }

    public function events(EventQuery $query = new EventQuery()): iterable
    {
        $answer = $this->api->request('GET', 'https://api.example.org/events', ['query' => ['key' => $this->apiKey]], 'the events');

        return $query->filter(array_map($this->toEvent(...), $answer->list('events')));
    }

    public function event(string $id): Event
    {
        return $this->toEvent($this->api->request('GET', 'https://api.example.org/events/'.rawurlencode($id), ['query' => ['key' => $this->apiKey]], 'event "'.$id.'"'));
    }

    private function toEvent(Data $line): Event
    {
        return new Event((string) $line->string('id'), $line->string('title') ?? '', $line->date('start'), provider: 'example');
    }
}
```

The address and the fields are placeholders. Each provider's own manual lists its calls, the
mapping of its statuses and what it does not read.

## What the five providers keep to

- The HTTP client injected, never created: a factory without one refuses to build.
- Credentials as `#[\SensitiveParameter]` constructor arguments.
- Listings returned through `$query->filter()`; where the API pages (HelloAsso, Eventbrite,
  Ticketmaster), pages fetched as the iteration goes (a generator), so a limit stops the paging.
- Dates read in the service's zone when it gives local times (Billetweb, Weezevent: the
  `timezone` option); prices in the currency it names, or the `currency` option.
- Nothing written: no check marks a ticket as scanned.
