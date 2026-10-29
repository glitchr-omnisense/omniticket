<?php

namespace Omniticket\Tests;

use Omniticket\Capabilities;
use Omniticket\Config;
use Omniticket\EventCatalogInterface;
use Omniticket\Exception\InvalidConfigException;
use Omniticket\Exception\InvalidKeyException;
use Omniticket\Exception\NotFoundException;
use Omniticket\Exception\NotSupportedException;
use Omniticket\Exception\ProviderException;
use Omniticket\Http\JsonApi;
use Omniticket\Model\Event;
use Omniticket\Model\EventQuery;
use Omniticket\ProviderFactory;
use Omniticket\ProviderInterface;
use Omniticket\Registry;
use Omniticket\Testing\FixedTicketingFactory;
use Omniticket\Testing\RecordedHttpClient;
use PHPUnit\Framework\TestCase;

final class RegistryTest extends TestCase
{
    /** Not incremental, as its capabilities say: a read since a date is refused, as the contract asks. */
    public function testTheFixedProviderRefusesAnIncrementalRead(): void
    {
        $attendees = (new Registry([new FixedTicketingFactory([], ['1' => [new \Omniticket\Model\Attendee('a', '1', 'T1')]])], ['b' => ['factory' => 'fixed']]))->attendees('b');

        self::assertCount(1, [...$attendees->attendees('1')]);
        self::assertFalse($attendees->capabilities()->incremental);
        $this->expectException(NotSupportedException::class);
        [...$attendees->attendees('1', new \DateTimeImmutable('2026-10-01'))];
    }

    public function testProvidersByNameEachBuiltOnce(): void
    {
        $registry = new Registry([new FixedTicketingFactory([new Event('1', 'Show')]), self::catalogOnly()], [
            'box_office' => ['factory' => 'fixed'],
            'public' => ['factory' => 'catalog', 'options' => ['api_key' => 'example-not-a-key']],
        ]);

        self::assertSame($registry->get('box_office'), $registry->catalog('box_office'));
        self::assertSame($registry->get('box_office'), $registry->attendees('box_office'));
        self::assertSame($registry->get('box_office'), $registry->checker('box_office'));
        self::assertSame(['box_office', 'public'], $registry->names());
        self::assertSame(['fixed', 'catalog'], $registry->factories());
        self::assertSame(['api_key' => 'example-not-a-key'], $registry->options('public'));
        self::assertSame(['catalog', 'attendees', 'check'], $registry->get('box_office')->capabilities()->contracts());
        self::assertSame('Show', $registry->catalog('box_office')->event('1')->title);

        try {
            $registry->attendees('public');
            self::fail('A read-only catalogue.');
        } catch (NotSupportedException $e) {
            self::assertStringContainsString('does not list attendees', $e->getMessage());
        }
        $this->expectException(InvalidConfigException::class);
        $registry->get('elsewhere');
    }

    public function testARequiredOption(): void
    {
        $this->expectExceptionMessage('The "catalog" provider needs: api_key.');
        (new Registry([self::catalogOnly()], ['public' => ['factory' => 'catalog']]))->get('public');
    }

    public function testAnUnknownEvent(): void
    {
        $this->expectException(NotFoundException::class);
        (new Registry([new FixedTicketingFactory()], ['b' => ['factory' => 'fixed']]))->catalog('b')->event('nope');
    }

    public function testConfigAndCapabilities(): void
    {
        $config = new Config(['a' => 'yes', 'b' => '0', 'c' => '12', 'd' => '']);

        self::assertSame([true, false, 12, null, 'fallback'], [$config->bool('a'), $config->bool('b'), $config->int('c'), $config->string('d'), $config->defaults(['d2' => 'fallback'])->get('d2')]);
        self::assertSame(['contracts' => ['catalog'], 'catalog' => true, 'attendees' => false, 'check' => false, 'incremental' => false, 'widget' => false, 'terms' => [], 'limits' => ['5 a second']], (new Capabilities(catalog: true, limits: ['5 a second']))->toArray());
    }

    public function testJsonApiTurnsFailuresIntoOmniticketExceptions(): void
    {
        $api = new JsonApi(RecordedHttpClient::routes([
            'GET /ok' => '{"events":[{"id":1}]}',
            'GET /refused' => [403, '{"error_description":"scope missing"}'],
            'GET /broken' => [500, '{"errors":[{"message":"boom"}]}'],
            'GET /html' => '<html>',
        ]), 'Example');

        self::assertSame(1, $api->request('GET', 'https://x.example/ok')->list('events')[0]->int('id'));
        foreach (['/refused' => [InvalidKeyException::class, 'Example refused the credentials (403) for it: scope missing'], '/missing' => [NotFoundException::class, 'Example has no it: not recorded: GET /missing'], '/broken' => [ProviderException::class, 'Example answered 500 for it: boom'], '/html' => [ProviderException::class, 'Example answered 200 for it: an answer that is not JSON']] as $path => [$class, $message]) {
            try {
                $api->request('GET', 'https://x.example'.$path, [], 'it');
                self::fail($path);
            } catch (ProviderException $e) {
                self::assertSame([$class, $message], [$e::class, $e->getMessage()]);
            }
        }
    }

    private static function catalogOnly(): ProviderFactory
    {
        return new class extends ProviderFactory {
            protected function populate(Config $c): void
            {
                $c->defaults(['omniticket.factory_name' => 'catalog', 'omniticket.factory_title' => 'Catalog', 'omniticket.required_options' => ['api_key']]);
            }

            protected function build(Config $c): ProviderInterface
            {
                return new class implements EventCatalogInterface {
                    public function getName(): string
                    {
                        return 'catalog';
                    }

                    public function getTitle(): string
                    {
                        return 'Catalog';
                    }

                    public function capabilities(): Capabilities
                    {
                        return new Capabilities(catalog: true);
                    }

                    public function events(EventQuery $query = new EventQuery()): iterable
                    {
                        return [];
                    }

                    public function event(string $id): Event
                    {
                        throw NotFoundException::of('Catalog', $id);
                    }
                };
            }
        };
    }
}
