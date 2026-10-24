<?php

namespace Omniticket\Model;

/**
 * An event as the ticketing service sells it. events() fills what a list
 * gives; event() the whole record - its rates, sessions and places left
 * when the provider has them.
 */
final readonly class Event
{
    /**
     * @param ?string       $url       where a visitor buys a ticket (the provider's page)
     * @param ?string       $widgetUrl the provider's purchase module, to embed in a frame, when it has one
     * @param list<Rate>    $rates
     * @param list<Session> $sessions
     * @param ?Money        $priceFrom the lowest price, when the provider gives a range rather than rates
     */
    public function __construct(
        public string $id,
        public string $title,
        public ?\DateTimeImmutable $start = null,
        public ?\DateTimeImmutable $end = null,
        public EventStatus $status = EventStatus::Unknown,
        public ?string $url = null,
        public ?string $venue = null,
        public Availability $availability = new Availability(),
        public array $rates = [],
        public array $sessions = [],
        public ?Money $priceFrom = null,
        public ?Money $priceTo = null,
        public ?string $widgetUrl = null,
        public string $provider = '',
    ) {
    }

    /** Tickets can be bought now, as far as the provider says. */
    public function onSale(): bool
    {
        return EventStatus::OnSale === $this->status && !$this->availability->soldOut;
    }

    public function soldOut(): bool
    {
        return EventStatus::SoldOut === $this->status || $this->availability->soldOut;
    }

    /** The lowest price among the rates, or the range's lower end. */
    public function lowestPrice(): ?Money
    {
        $lowest = $this->priceFrom;
        foreach ($this->rates as $rate) {
            if (null !== $rate->price && (null === $lowest || $rate->price->amount < $lowest->amount)) {
                $lowest = $rate->price;
            }
        }

        return $lowest;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'start' => $this->start?->format(\DATE_ATOM),
            'end' => $this->end?->format(\DATE_ATOM),
            'status' => $this->status->value,
            'url' => $this->url,
            'venue' => $this->venue,
            'availability' => $this->availability->toArray(),
            'rates' => array_map(static fn (Rate $rate) => $rate->toArray(), $this->rates),
            'sessions' => array_map(static fn (Session $session) => $session->toArray(), $this->sessions),
            'priceFrom' => $this->priceFrom?->toArray(),
            'priceTo' => $this->priceTo?->toArray(),
            'widgetUrl' => $this->widgetUrl,
            'provider' => $this->provider,
        ];
    }
}
