<?php

namespace Omniticket;

use Omnisense\Contracts\Policy\Provenance;
use Omnisense\Contracts\Policy\RateLimit;
use Omnisense\Contracts\Policy\UsagePolicy;
use Omnisense\Contracts\Provider\CapabilitiesInterface;

/**
 * What a provider is, said before it is asked anything: what it can read,
 * and its usage policy - the rate limits and terms whoever runs it must
 * keep.
 *
 * The policy is omnisense/contracts' UsagePolicy. The two fields written
 * before it ($terms, $limits, in words) stay, read from the policy when it
 * is given and they are not: a provider that gives them alone gets a
 * policy made of them, its limits unknown (the words in its notes: free
 * text is not read as figures).
 */
final readonly class Capabilities implements CapabilitiesInterface
{
    /**
     * @var list<string>
     *
     * @deprecated since 1.x, read policy()->provenance (terms, policy)
     */
    public array $terms;

    /**
     * @var list<string>
     *
     * @deprecated since 1.x, read policy()->rateLimits (and policy()->provenance->notes)
     */
    public array $limits;

    private UsagePolicy $policy;

    /**
     * @param bool         $catalog     it lists and reads events (EventCatalogInterface)
     * @param bool         $attendees   it lists an event's attendees (AttendeeListInterface)
     * @param bool         $check       it checks a ticket code (TicketCheckInterface)
     * @param bool         $incremental its attendees can be read from a moment on (only those changed since)
     * @param bool         $widget      its events carry a purchase module to embed (Event::$widgetUrl)
     * @param list<string> $terms       the terms whoever runs it must follow (URLs)
     * @param list<string> $limits      the rate limits and usage rules the provider states, in words
     * @param ?UsagePolicy $policy      its usage policy; made of $terms and $limits when null
     */
    public function __construct(
        public bool $catalog = false,
        public bool $attendees = false,
        public bool $check = false,
        public bool $incremental = false,
        public bool $widget = false,
        array $terms = [],
        array $limits = [],
        ?UsagePolicy $policy = null,
    ) {
        $this->policy = $policy ?? self::described($terms, $limits);
        $this->terms = [] !== $terms || null === $policy ? $terms : array_values(array_unique(array_filter([$policy->provenance->terms, $policy->provenance->policy])));
        $this->limits = [] !== $limits || null === $policy ? $limits : array_map(static fn (RateLimit $limit) => $limit->describe(), $policy->rateLimits ?? []);
    }

    public function policy(): UsagePolicy
    {
        return $this->policy;
    }

    /** @return list<string> what it can be asked, by contract: catalog, attendees, check */
    public function contracts(): array
    {
        return array_keys(array_filter(['catalog' => $this->catalog, 'attendees' => $this->attendees, 'check' => $this->check]));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['contracts' => $this->contracts(), 'catalog' => $this->catalog, 'attendees' => $this->attendees, 'check' => $this->check, 'incremental' => $this->incremental, 'widget' => $this->widget, 'terms' => $this->terms, 'limits' => $this->limits];
    }

    /**
     * The older fields as a policy: the terms' first address, the limits in
     * words kept in the notes, nothing else known.
     *
     * @param list<string> $terms
     * @param list<string> $limits
     */
    private static function described(array $terms, array $limits): UsagePolicy
    {
        $notes = array_filter([[] === $terms ? null : 'Terms: '.implode(', ', $terms), [] === $limits ? null : 'Limits, in words: '.implode('; ', $limits)]);

        return new UsagePolicy(UsagePolicy::UNNAMED, provenance: new Provenance(terms: $terms[0] ?? null, notes: [] === $notes ? null : implode('. ', $notes).'.'));
    }
}
