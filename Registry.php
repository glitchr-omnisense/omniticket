<?php

namespace Omniticket;

use Omnisense\Contracts\Policy\RateLimiterInterface;
use Omnisense\Contracts\Provider\ProviderFactoryInterface as BaseFactory;
use Omnisense\Contracts\Provider\ProviderInterface as BaseProvider;
use Omnisense\Contracts\Provider\Registry as BaseRegistry;
use Omniticket\Exception\InvalidConfigException;
use Omniticket\Exception\NotSupportedException;
use Omniticket\Policy\GuardedAttendees;
use Omniticket\Policy\GuardedCatalog;
use Omniticket\Policy\GuardedChecker;

/**
 * The application's ticketing providers by name, each built once from its
 * factory and options, each call through a contract guarded by the
 * provider's usage policy:
 *
 *   $registry = new Registry([new BilletwebFactory($http), new TicketmasterFactory($http)], [
 *       'box_office' => ['factory' => 'billetweb', 'options' => ['user' => '...', 'key' => '...']],
 *       'concerts' => [
 *           'factory' => 'ticketmaster',
 *           'options' => ['api_key' => '...'],
 *           'policy' => ['rateLimits' => [['max' => 1000, 'window' => 'day']]],
 *       ],
 *   ], $limiter);
 *
 *   foreach ($registry->catalog('box_office')->events() as $event) { ... }
 *   $registry->checker('box_office')->check('1235', '4475320')->valid();
 *
 * catalog(), attendees() and checker() give the provider behind its guard
 * (Policy\GuardedCatalog, GuardedAttendees, GuardedChecker; inner() gives
 * the provider): each call is checked against the policy's rate limits for
 * its operation - the contract's method name, "events", "event",
 * "attendees", "check" - then counted, and refused with
 * Exception\UsageLimitReachedException once a limit is reached or while
 * the provider asks to wait. get() gives the provider itself, unguarded.
 * "policy" tightens the provider's policy, never loosens it; "usage" says
 * what the site is (omnisense/contracts' Registry).
 */
final class Registry extends BaseRegistry
{
    /** @var string */
    public const PREFIX = Config::PREFIX;

    /** @var array<string, ProviderInterface> the guarded contracts, by "contract name" */
    private array $guarded = [];

    /**
     * @param iterable<ProviderFactoryInterface>                                                                                         $factories
     * @param array<string, array{factory: string, options?: array<string, mixed>, policy?: array<string, mixed>, usage?: array<string, mixed>}> $config
     * @param ?RateLimiterInterface                                                                                                      $limiter   where every provider's calls are counted: an in-memory one by default, a shared one for an application served by many processes
     */
    public function __construct(iterable $factories, array $config, ?RateLimiterInterface $limiter = null)
    {
        parent::__construct($factories, $config, $limiter);
    }

    public function get(string $name): ProviderInterface
    {
        $provider = parent::get($name);

        return $provider instanceof ProviderInterface ? $provider : throw new InvalidConfigException(\sprintf('The "%s" ticketing provider (%s) is no omniticket provider.', $name, $provider::class));
    }

    /** The provider, as a catalogue of events, behind its guard. */
    public function catalog(string $name): EventCatalogInterface
    {
        $guarded = $this->guarded['catalog '.$name] ??= new GuardedCatalog($this->contract($name, EventCatalogInterface::class, 'list events'), $this->guard($name));

        return $guarded instanceof EventCatalogInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    /** The provider, as an event's attendees, behind its guard. */
    public function attendees(string $name): AttendeeListInterface
    {
        $guarded = $this->guarded['attendees '.$name] ??= new GuardedAttendees($this->contract($name, AttendeeListInterface::class, 'list attendees'), $this->guard($name));

        return $guarded instanceof AttendeeListInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    /** The provider, as a ticket check, behind its guard. */
    public function checker(string $name): TicketCheckInterface
    {
        $guarded = $this->guarded['checker '.$name] ??= new GuardedChecker($this->contract($name, TicketCheckInterface::class, 'check tickets'), $this->guard($name));

        return $guarded instanceof TicketCheckInterface ? $guarded : throw new \LogicException('A guarded contract of another kind.');
    }

    /**
     * The configured providers' names, in the configuration's order; those
     * keeping a contract ("catalog", "attendees", "check") when one is named.
     *
     * @return list<string>
     */
    public function names(?string $contract = null): array
    {
        return parent::names($contract);
    }

    protected function factory(string $name): BaseFactory
    {
        $provider = $this->config[$name] ?? throw new InvalidConfigException(\sprintf('No "%s" ticketing provider; configured: %s.', $name, implode(', ', array_keys($this->config)) ?: 'none'));

        return $this->factories[$provider['factory']] ?? throw new InvalidConfigException(\sprintf('No "%s" factory for the "%s" ticketing provider; installed: %s.', $provider['factory'], $name, implode(', ', array_keys($this->factories)) ?: 'none'));
    }

    protected function invalid(string $message): \Throwable
    {
        return new InvalidConfigException($message);
    }

    protected function notSupported(BaseProvider $provider, string $name, string $capability): \Throwable
    {
        return NotSupportedException::capability($provider, $name, $capability);
    }
}
