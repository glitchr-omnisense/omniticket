<?php

namespace Omniticket\Model;

/**
 * A price: an amount in the currency's minor unit (cents; yen have none)
 * and its ISO 4217 code - never a float, so 9.99 stays 999.
 */
final readonly class Money
{
    /** Currencies without a minor unit (ISO 4217 exponent 0) among those a ticket is commonly sold in. */
    private const NO_MINOR_UNIT = ['BIF', 'CLP', 'DJF', 'GNF', 'ISK', 'JPY', 'KMF', 'KRW', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];

    public string $currency;

    public function __construct(public int $amount, string $currency)
    {
        $this->currency = strtoupper($currency);
    }

    /** From a price in units ("12.50", 9.99, 10), as most APIs give it. */
    public static function fromMajor(int|float|string $major, string $currency): self
    {
        return new self((int) round((float) $major * 10 ** self::exponent($currency)), $currency);
    }

    public static function exponent(string $currency): int
    {
        return \in_array(strtoupper($currency), self::NO_MINOR_UNIT, true) ? 0 : 2;
    }

    /** The amount in units, as a decimal string: "12.50", "3000". */
    public function major(): string
    {
        $exponent = self::exponent($this->currency);

        return number_format($this->amount / 10 ** $exponent, $exponent, '.', '');
    }

    public function isFree(): bool
    {
        return 0 === $this->amount;
    }

    /** @return array{amount: int, currency: string, major: string} */
    public function toArray(): array
    {
        return ['amount' => $this->amount, 'currency' => $this->currency, 'major' => $this->major()];
    }
}
