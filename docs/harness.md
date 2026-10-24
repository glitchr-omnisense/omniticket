# The harness

`docker/` composes this core and every provider (`docker/harness/plugins.php`) into one
installation, PHP 8.4:

    docker compose run --rm omniticket test       # every package's tests, no network
    docker compose run --rm omniticket live       # the checks that need no account
    docker compose run --rm omniticket bare       # the bare script: plain PHP, and what PHP loaded
    docker compose run --rm omniticket phpstan    # PHPStan at the highest level, each package
    docker compose down -v

Providers come from GitHub (branch 1.x) unless `OMNITICKET_PLUGINS` (in `docker/.env`) points at a
directory holding their checkouts - `../..` when this core sits in the workspace beside them.
