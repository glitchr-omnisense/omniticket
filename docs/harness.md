---
title: The Docker harness
order: 70
---

# The Docker harness

`docker/` composes this core and every provider (`docker/harness/plugins.php`) into one
installation, PHP 8.4, in the image `omnisense/omniticket-harness`. `docker/harness/setup.php`
writes the harness's `composer.json` and `phpunit.xml` into a volume, and the entrypoint installs
them when they change.

```sh
cd docker
docker compose run --rm omniticket test       # every package's tests, no network (the live group left out)
docker compose run --rm omniticket live       # the checks that need no account (Weezevent's public endpoint)
docker compose run --rm omniticket bare       # the bare script: plain PHP, and what PHP loaded
docker compose run --rm omniticket phpstan    # PHPStan at the highest level, each package
docker compose run --rm -v "$PWD/scratch:/scratch" omniticket php /scratch/x.php   # a script of your own (autoloader: /harness/vendor/autoload.php)
docker compose down -v                        # the harness's volume gone
```

With no command the entrypoint runs `test`; `composer`, `sh` and `php` run as they are.
Providers come from GitHub (branch 1.x, `github.com/glitchr-omnisense/omniticket-<slug>`) unless
`OMNITICKET_PLUGINS` (in `docker/.env`) points at a directory holding their checkouts - `../..`
when this core sits in the workspace beside them, as `docker/.env` sets it.

## Bare

`docker/harness/bin/bare` uses Omniticket in plain PHP - no bundle, no container, no framework:
a registry with the fixed provider, the upcoming events listed (a past one left out, a keyword),
one read whole, its attendees, four codes checked at the door; then a JSON API answered through
the recorded HTTP client - an answer read with types, a refused key and a missing event turned
into the family's exceptions. It prints what happened and every class and file PHP loaded, as
JSON; run on 2026-10-26, without the `event` and `loaded` parts:

```
"upcoming": ["1235", "1236"],
"keyword": ["1236"],
"onSale": true,
"lowestPrice": "18.00",
"attendees": [["Jeanne Dupont", "valid"], ["Paul Martin", "checked_in"], ["Ana Lima", "refunded"]],
"checks": {"4475320": "valid", "4475321": "already_used", "4475322": "void", "0000000": "unknown"},
"api": ["Retro future shock", "2026-12-31T20:00:00+01:00"],
"errors": {"/api/private": [true, "Example refused the credentials (401) for it: invalid key"],
           "/api/event/9": [true, "Example has no it: not recorded: GET /api/event/9"]}
```

`Tests/BareTest.php` runs it in a process of its own and checks that nothing outside the package
was loaded but Symfony's HTTP client contracts. Each provider's `BareTest` runs
`Testing\LoadCheck` on its own classes.
