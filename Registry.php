<?php

namespace Omniticket;

use Omniticket\Exception\InvalidConfigException;
use Omniticket\Exception\NotSupportedException;

/**
 * The application's ticketing providers by name, each built once from its
 * factory and options:
 *
 *   $registry = new Registry([new BilletwebFactory($http), new TicketmasterFactory($http)], [
 *       'box_office' => ['factory' => 'billetweb', 'options' => ['user' => '...', 'key' => '...']],
 *       'concerts' => ['factory' => 'ticketmaster', 'options' => ['api_key' => '...']],
 *   ]);
 *
 *   foreach ($registry->catalog('box_office')->events() as $event) { ... }
 *   $registry->checker('box_office')->check('1235', '4475320')->valid();
 */
final class Registry
{
    /** @var array<string, ProviderFactoryInterface> */
    private array $factories = [];

    /** @var array<string, ProviderInterface> */
    private array $providers = [];

    /**
     * @param iterable<ProviderFactoryInterface>                                     $factories
     * @param array<string, array{factory: string, options?: array<string, mixed>}> $config
     */
    public function __construct(iterable $factories, private readonly array $config)
    {
        foreach ($factories as $factory) {
            $this->factories[$factory->getName()] = $factory;
        }
    }

    public function get(string $name): ProviderInterface
    {
        return $this->providers[$name] ??= $this->factory($name)->create($this->config[$name]['options'] ?? []);
    }

    public function has(string $name): bool
    {
        return isset($this->config[$name]);
    }

    /** The provider, as a catalogue of events. */
    public function catalog(string $name): EventCatalogInterface
    {
        $provider = $this->get($name);

        return $provider instanceof EventCatalogInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'list events');
    }

    /** The provider, as an event's attendees. */
    public function attendees(string $name): AttendeeListInterface
    {
        $provider = $this->get($name);

        return $provider instanceof AttendeeListInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'list attendees');
    }

    /** The provider, as a ticket check. */
    public function checker(string $name): TicketCheckInterface
    {
        $provider = $this->get($name);

        return $provider instanceof TicketCheckInterface ? $provider : throw NotSupportedException::capability($provider, $name, 'check tickets');
    }

    /** @return array<string, mixed> the options a provider is configured with */
    public function options(string $name): array
    {
        return $this->config[$name]['options'] ?? [];
    }

    /** @return list<string> the configured providers' names */
    public function names(): array
    {
        return array_keys($this->config);
    }

    /** @return list<string> the factories installed: what `factory:` may name */
    public function factories(): array
    {
        return array_keys($this->factories);
    }

    private function factory(string $name): ProviderFactoryInterface
    {
        $provider = $this->config[$name] ?? throw new InvalidConfigException(\sprintf('No "%s" ticketing provider; configured: %s.', $name, implode(', ', array_keys($this->config)) ?: 'none'));

        return $this->factories[$provider['factory']] ?? throw new InvalidConfigException(\sprintf('No "%s" factory for the "%s" ticketing provider; installed: %s.', $provider['factory'], $name, implode(', ', array_keys($this->factories)) ?: 'none'));
    }
}
