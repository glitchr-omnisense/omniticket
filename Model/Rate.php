<?php

namespace Omniticket\Model;

/** One price of an event: "Plein tarif", "Early bird", a tier. */
final readonly class Rate
{
    /**
     * @param ?Money $price   null when the provider gives none; for a pay-what-you-want rate, see $minimum
     * @param ?Money $minimum the least a buyer may pay when the price is theirs to choose
     */
    public function __construct(
        public string $id,
        public string $label,
        public ?Money $price = null,
        public Availability $availability = new Availability(),
        public ?\DateTimeImmutable $salesStart = null,
        public ?\DateTimeImmutable $salesEnd = null,
        public ?Money $minimum = null,
        public ?string $category = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'price' => $this->price?->toArray(),
            'availability' => $this->availability->toArray(),
            'salesStart' => $this->salesStart?->format(\DATE_ATOM),
            'salesEnd' => $this->salesEnd?->format(\DATE_ATOM),
            'minimum' => $this->minimum?->toArray(),
            'category' => $this->category,
        ];
    }
}
