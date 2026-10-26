---
title: Laravel
order: 16
---

# Laravel

omnibridge/laravel has no Omniticket provider: a service provider of the application builds
the registry once, from a config file, with a `symfony/http-client` client (the providers take a
`Symfony\Contracts\HttpClient\HttpClientInterface`; Laravel's own HTTP client is Guzzle's, which
they do not take).

```php
// config/omniticket.php
return [
    'providers' => [
        'festival' => ['factory' => 'weezevent', 'options' => [
            'api_key' => env('WEEZEVENT_API_KEY'),
            'access_token' => env('WEEZEVENT_ACCESS_TOKEN'),
        ]],
    ],
];
```

```php
// app/Providers/OmniticketServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Omniticket\Registry;
use Omniticket\Weezevent\WeezeventFactory;
use Symfony\Component\HttpClient\HttpClient;

final class OmniticketServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Registry::class, fn (): Registry => new Registry(
            [new WeezeventFactory(HttpClient::create(['timeout' => 20]))],
            config('omniticket.providers'),
        ));
    }
}
```

Register the provider (`bootstrap/providers.php` on Laravel 11 and later), then:

```php
use Illuminate\Support\Facades\Cache;
use Omniticket\Registry;

$events = Cache::remember('programme', 600, fn () => array_map(
    static fn ($e) => $e->toArray(),
    [...app(Registry::class)->catalog('festival')->events()],
));
```

Keep arrays in the cache (`toArray()` on every model). Configuration caching keeps working: the
options are plain arrays. This page was written from the registry's API; it was not run in a
Laravel application.
