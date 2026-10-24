<?php

namespace Omniticket\Tests;

use Omniticket\Registry;
use Omniticket\Support\Data;
use Omniticket\Testing\LoadCheck;
use PHPUnit\Framework\TestCase;

/**
 * omniscient/omniticket with no framework: in PHP processes of their own
 * (this one has loaded PHPUnit), the harness's bare script lists, reads
 * and checks through a Registry and calls a recorded JSON API - and every
 * class of the package is loaded. Nothing may come from outside the
 * package but what it requires: Symfony's HTTP client contracts
 * (interfaces only) - no framework, no HTTP client, no application, even
 * in the harness where they are installed beside it.
 */
final class BareTest extends TestCase
{
    private const ALLOWED = ['/vendor/symfony/http-client-contracts/', '/vendor/symfony/service-contracts/'];

    public function testListedReadCheckedAndCalledByHandLoadsNothingButOmniticketAndWhatItRequires(): void
    {
        $autoload = self::autoload();
        $process = proc_open([\PHP_BINARY, __DIR__.'/../docker/harness/bin/bare'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, ['OMNITICKET_AUTOLOAD' => $autoload] + getenv());
        self::assertIsResource($process);
        $out = (string) stream_get_contents($pipes[1]);
        $err = (string) stream_get_contents($pipes[2]);
        self::assertSame(0, proc_close($process), $err.$out);
        $report = Data::of(json_decode($out, true));

        self::assertSame(['1235', '1236'], $report->raw('upcoming'), 'the past event left out');
        self::assertSame(['1236'], $report->raw('keyword'));
        self::assertSame('Retro future shock', $report->string('event.title'));
        self::assertSame(80, $report->int('event.availability.remaining'), '200 places, 120 sold');
        self::assertTrue($report->bool('onSale'));
        self::assertSame('18.00', $report->string('lowestPrice'));
        self::assertSame([['Jeanne Dupont', 'valid'], ['Paul Martin', 'checked_in'], ['Ana Lima', 'refunded']], $report->raw('attendees'));
        self::assertSame(['4475320' => 'valid', '4475321' => 'already_used', '4475322' => 'void', '0000000' => 'unknown'], $report->raw('checks'));
        self::assertSame(['Retro future shock', '2026-12-31T20:00:00+01:00'], $report->raw('api'));
        self::assertTrue($report->bool('errors./api/private.0'), 'a 401 is an InvalidKeyException');
        self::assertSame('Example refused the credentials (401) for it: invalid key', $report->string('errors./api/private.1'));
        self::assertTrue($report->bool('errors./api/event/9.0'), 'a 404 is a NotFoundException');

        $symbols = array_values(array_filter((array) $report->raw('loaded.symbols'), 'is_string'));
        self::assertContains('Omniticket\Registry', $symbols);
        self::assertSame([], LoadCheck::foreign($symbols), 'no class of a framework, an HTTP client or an application');
        $eager = array_map('dirname', array_values(array_filter((array) $report->raw('loaded.eager'), 'is_string')));
        $files = array_values(array_filter((array) $report->raw('loaded.files'), static fn ($f) => \is_string($f) && !\in_array(\dirname($f), $eager, true)));
        self::assertSame([], LoadCheck::outside(array_values(array_filter($files, 'is_string')), \dirname(__DIR__), self::ALLOWED), 'no file but the package and what it requires');
    }

    public function testEveryClassLoadsWithTheRequiredPackagesAlone(): void
    {
        $report = LoadCheck::run(\dirname(__DIR__), 'Omniticket\\', self::autoload());

        self::assertGreaterThan(25, \count($report['loaded']));
        self::assertContains('Omniticket\Testing\FixedTicketing', $report['loaded']);
        self::assertSame([], $report['foreign']);
        self::assertSame([], LoadCheck::outside($report['files'], \dirname(__DIR__), self::ALLOWED));
    }

    /** The check is not blind: an HTTP client's class, once loaded, is seen. */
    public function testTheReportSeesWhatIsLoaded(): void
    {
        if (!class_exists(\Symfony\Component\HttpClient\MockHttpClient::class)) {
            self::markTestSkipped('symfony/http-client is not installed.');
        }
        $report = LoadCheck::run(\dirname(__DIR__), 'Omniticket\\', self::autoload(), [\Symfony\Component\HttpClient\MockHttpClient::class]);

        self::assertContains(\Symfony\Component\HttpClient\MockHttpClient::class, $report['foreign']);
        self::assertNotEmpty(preg_grep('~/symfony/http-client/~', LoadCheck::outside($report['files'], \dirname(__DIR__), self::ALLOWED)));
    }

    private static function autoload(): string
    {
        return LoadCheck::autoload(Registry::class, self::class, \dirname(__DIR__)) ?? self::markTestSkipped('omniticket is installed in an application here, or no autoloader knows its tests: the bare check is the package\'s own.');
    }
}
