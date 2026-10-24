# Installation, in plain PHP

    composer require omniscient/omniticket omniticket/billetweb symfony/http-client

Any implementation of `Symfony\Contracts\HttpClient\HttpClientInterface` does: Symfony's
`HttpClient::create()`, or a `MockHttpClient` in a test.

```php
use Omniticket\Billetweb\BilletwebFactory;
use Omniticket\Model\{CheckResult, EventQuery};
use Omniticket\Registry;
use Omniticket\Weezevent\WeezeventFactory;
use Symfony\Component\HttpClient\HttpClient;

$http = HttpClient::create();
$registry = new Registry([new BilletwebFactory($http), new WeezeventFactory($http)], [
    'box_office' => ['factory' => 'billetweb', 'options' => ['user' => getenv('BILLETWEB_USER'), 'key' => getenv('BILLETWEB_KEY')]],
    'festival' => ['factory' => 'weezevent', 'options' => ['api_key' => getenv('WEEZEVENT_API_KEY'), 'access_token' => getenv('WEEZEVENT_TOKEN')]],
]);

// The next events, whatever the provider: the query is applied the same way to every answer.
$events = $registry->catalog('box_office')->events(new EventQuery(from: new DateTimeImmutable('first day of next month'), limit: 20));

// One event whole: rates with their prices (Money, minor units) and places left, sessions.
$event = $registry->catalog('box_office')->event('1235');
foreach ($event->rates as $rate) {
    printf("%s: %s %s, %s left\n", $rate->label, $rate->price?->major(), $rate->price?->currency, $rate->availability->remaining ?? '?');
}

// Who comes; only what changed since the last read where the provider allows it.
foreach ($registry->attendees('box_office')->attendees('1235', $lastRead) as $attendee) {
    // store $attendee->toArray() on your side: the providers ask for it (fair use)
}

// At the door: reading only, the ticket is not marked as scanned.
$check = $registry->checker('box_office')->check('1235', $scannedCode);
echo CheckResult::Valid === $check->result ? 'OK '.$check->attendee?->name() : $check->result->value;
```

`Registry::catalog()`, `attendees()` and `checker()` throw `NotSupportedException` when the
provider has no such capability (Ticketmaster is a catalogue only); `Capabilities` says it
beforehand. Every failure is an `Omniticket\Exception\OmniticketException`.
