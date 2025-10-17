<?php

declare(strict_types=1);

namespace Adeliom\SyliusExchangeRatePlugin\Provider;

use Adeliom\SyliusExchangeRatePlugin\Provider\DTO\ExchangeRateData;

/**
 * Interface for exchange rate providers.
 *
 * Implement this interface to create a new exchange rate provider.
 * All providers are automatically discovered via the 'adeliom.exchange_rate_provider' tag.
 */
interface ExchangeRateProviderInterface
{
    /**
     * Fetches exchange rates from the external provider.
     *
     * @return ExchangeRateData[] Array of normalized exchange rate data
     *
     * @throws \Exception If the API request fails
     */
    public function fetchRates(): array;

    /**
     * Returns the name of the provider (for logging and identification).
     */
    public function getName(): string;

    /**
     * Checks if the provider is enabled and properly configured.
     */
    public function isEnabled(): bool;
}
