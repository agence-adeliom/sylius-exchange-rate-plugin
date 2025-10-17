<?php

declare(strict_types=1);

namespace Adeliom\SyliusExchangeRatePlugin\Provider\DTO;

/**
 * Data Transfer Object for normalized exchange rate data.
 *
 * This class ensures consistent data structure across all providers.
 */
final class ExchangeRateData
{
    public function __construct(
        public readonly string $sourceCurrency,
        public readonly string $targetCurrency,
        public readonly float $ratio,
        public readonly \DateTimeInterface $date,
    ) {
    }

    public function toArray(): array
    {
        return [
            'source_currency' => $this->sourceCurrency,
            'target_currency' => $this->targetCurrency,
            'ratio' => $this->ratio,
            'date' => $this->date->format('Y-m-d H:i:s'),
        ];
    }
}
