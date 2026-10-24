<?php

namespace Omniticket;

/** Builds a provider from its options (a key, a secret, an organization...). */
interface ProviderFactoryInterface
{
    /** The name providers are configured with: "weezevent", "billetweb"... */
    public function getName(): string;

    /** @param array<string, mixed> $options */
    public function create(array $options = []): ProviderInterface;
}
