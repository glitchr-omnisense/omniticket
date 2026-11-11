<?php

namespace Omniticket\Tests;

use Omnisense\Contracts\Policy\InvalidPolicyException;
use Omnisense\Contracts\Policy\Permission;
use Omnisense\Contracts\Policy\RateLimit;
use Omnisense\Contracts\Policy\Rights;
use Omnisense\Contracts\Policy\UsageNotPermitted;
use Omnisense\Contracts\Policy\UsagePolicy;
use Omniticket\Capabilities;
use Omniticket\Config;
use Omniticket\EventCatalogInterface;
use Omniticket\Exception\OmniticketException;
use Omniticket\Exception\ProviderException;
use Omniticket\Exception\UsageLimitReachedException;
use Omniticket\Http\JsonApi;
use Omniticket\Model\Event;
use Omniticket\Model\EventQuery;
use Omniticket\Policy\GuardedCatalog;
use Omniticket\ProviderFactory;
use Omniticket\ProviderInterface;
use Omniticket\Registry;
use Omniticket\Testing\FixedTicketingFactory;
use Omniticket\Testing\RecordedHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * The usage policy through omniticket's registry: a site's limit counted
 * with the call over it never made, a 429 and its Retry-After holding every
 * next call, a tightening accepted, a looser one and a commercial site
 * refused, the older Capabilities fields made into a policy and read from
 * one. No network: recorded answers only.
 */
final class PolicyTest extends TestCase
{
    public function testASitesLimitIsCountedAndTheCallOverItIsNeverMade(): void
    {
        $http = RecordedHttpClient::routes(['GET /events/1' => '{"id":"1","name":"Show"}']);
        $registry = new Registry([self::factory($http)], ['box' => ['factory' => 'api', 'policy' => ['rateLimits' => [['max' => 2, 'window' => 'hour', 'operations' => ['event']]]]]]);

        self::assertInstanceOf(GuardedCatalog::class, $registry->catalog('box'));
        self::assertSame(['Show', 'Show'], [$registry->catalog('box')->event('1')->title, $registry->catalog('box')->event('1')->title]);
        try {
            $registry->catalog('box')->event('1');
            self::fail('The call over the site\'s limit was made.');
        } catch (UsageLimitReachedException $e) {
            self::assertInstanceOf(OmniticketException::class, $e, 'the family\'s catch holds it');
            self::assertSame('2/hour/application/event', $e->limit?->id());
            self::assertGreaterThan(0, $e->retryAfter);
        }
        self::assertCount(2, $http->requests, 'the refused call reached nothing');
        self::assertSame(0, $registry->guard('box')->remaining('event'));
        self::assertSame([], [...$registry->catalog('box')->events()], 'another operation counts apart');
    }

    public function testA429AndItsRetryAfterHoldEveryCallUntilTheWaitIsOver(): void
    {
        $http = RecordedHttpClient::routes(['GET /events/1' => [429, '{"message":"slow down"}', ['retry-after' => ['90']]]]);
        $registry = new Registry([self::factory($http)], ['box' => ['factory' => 'api']]);

        try {
            $registry->catalog('box')->event('1');
            self::fail('A 429 was read as an answer.');
        } catch (UsageLimitReachedException $e) {
            self::assertSame(90, $e->retryAfter);
            self::assertSame('api answered 429 Too Many Requests: try again in 90 s.', $e->getMessage());
        }
        try {
            $registry->catalog('box')->event('1');
            self::fail('A call went out while the provider asked to wait.');
        } catch (UsageLimitReachedException $e) {
            self::assertGreaterThanOrEqual(89, $e->retryAfter);
        }
        self::assertCount(1, $http->requests, 'the wait held the next request back');
    }

    public function testWithoutABackoffA429IsAnErrorAsBefore(): void
    {
        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('Example answered 429 for it: slow down');
        (new JsonApi(RecordedHttpClient::routes(['GET /x' => [429, '{"message":"slow down"}', ['retry-after' => ['90']]]]), 'Example'))->request('GET', 'https://api.example/x', [], 'it');
    }

    public function testATighteningIsAcceptedALooserLimitRefused(): void
    {
        $tightened = new Registry([self::factory(RecordedHttpClient::routes([]))], ['box' => ['factory' => 'api', 'options' => ['policy' => ['rateLimits' => [['max' => 100, 'window' => 'hour']], 'rights' => ['caching' => ['maxTtl' => 60]]]]]]);
        self::assertSame(['1000/hour/application/*', '100/hour/application/*'], array_map(static fn (RateLimit $l) => $l->id(), $tightened->policy('box')->rateLimits ?? []));
        self::assertSame(60, $tightened->guard('box')->cacheTtl());
        self::assertSame('api', $tightened->policy('box')->provider, 'the provider\'s name given to the policy its Capabilities carry');

        $looser = new Registry([self::factory(RecordedHttpClient::routes([]))], ['box' => ['factory' => 'api', 'policy' => ['rateLimits' => [['max' => 5000, 'window' => 'hour']]]]]);
        $this->expectException(InvalidPolicyException::class);
        $looser->catalog('box');
    }

    public function testACommercialSiteIsRefusedWhereTheTermsForbidIt(): void
    {
        $registry = new Registry([self::factory(RecordedHttpClient::routes([]))], ['box' => ['factory' => 'api', 'usage' => ['commercial' => true]]]);

        $this->expectException(UsageNotPermitted::class);
        $registry->catalog('box');
    }

    public function testTheOlderFieldsMakeAPolicyAndAreReadFromOne(): void
    {
        $old = new Capabilities(catalog: true, terms: ['https://example.org/terms'], limits: ['5 a second']);
        self::assertNull($old->policy()->rateLimits, 'words are not read as figures');
        self::assertSame('https://example.org/terms', $old->policy()->provenance->terms);
        self::assertStringContainsString('5 a second', (string) $old->policy()->provenance->notes);
        self::assertSame(['5 a second'], $old->limits);

        $new = new Capabilities(catalog: true, policy: self::policy());
        self::assertSame(['https://example.org/terms'], $new->terms);
        self::assertSame(['1000 an hour'], $new->limits);

        $fixed = new Registry([new FixedTicketingFactory([new Event('1', 'Show')])], ['b' => ['factory' => 'fixed']]);
        self::assertSame('fixed', $fixed->policy('b')->provider);
        self::assertFalse($fixed->policy('b')->knowsLimits());
        self::assertSame(['b' => $fixed->policy('b')], $fixed->policies());
        self::assertSame(['b'], $fixed->names('attendees'));
    }

    private static function policy(): UsagePolicy
    {
        return new UsagePolicy(UsagePolicy::UNNAMED, [RateLimit::perHour(1000)], new Rights(commercial: Permission::Forbidden), provenance: new \Omnisense\Contracts\Policy\Provenance(terms: 'https://example.org/terms'));
    }

    private static function factory(RecordedHttpClient $http): ProviderFactory
    {
        return new class($http) extends ProviderFactory {
            public function __construct(private readonly RecordedHttpClient $http)
            {
            }

            protected function populate(Config $c): void
            {
                $c->defaults(['omniticket.factory_name' => 'api', 'omniticket.factory_title' => 'Example', 'omniticket.required_options' => []]);
            }

            protected function build(Config $c): ProviderInterface
            {
                return new class(new JsonApi($this->http, 'Example', $c->backoff()), PolicyTest::policyOf()) implements EventCatalogInterface {
                    public function __construct(private readonly JsonApi $api, private readonly UsagePolicy $policy)
                    {
                    }

                    public function getName(): string
                    {
                        return 'api';
                    }

                    public function getTitle(): string
                    {
                        return 'Example';
                    }

                    public function capabilities(): Capabilities
                    {
                        return new Capabilities(catalog: true, policy: $this->policy);
                    }

                    public function events(EventQuery $query = new EventQuery()): iterable
                    {
                        return [];
                    }

                    public function event(string $id): Event
                    {
                        $data = $this->api->request('GET', 'https://api.example/events/'.$id, [], 'event '.$id);

                        return new Event((string) $data->string('id'), (string) $data->string('name'));
                    }
                };
            }
        };
    }

    /** @internal the test provider's policy */
    public static function policyOf(): UsagePolicy
    {
        return self::policy();
    }
}
