<?php

declare(strict_types=1);

namespace Adeliom\SyliusExchangeRatePlugin\Provider;

use Adeliom\SyliusExchangeRatePlugin\Provider\DTO\ExchangeRateData;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Fixer.io exchange rate provider.
 *
 * Fetches exchange rates from Fixer.io API.
 * Requires an API key (free tier available at https://fixer.io).
 *
 * Configuration:
 * - FIXER_API_KEY: Your Fixer.io API key (required)
 * - FIXER_BASE_CURRENCY: Base currency for rates (default: EUR)
 *
 * @see https://fixer.io/documentation
 */
final class FixerProvider implements ExchangeRateProviderInterface
{
    private const FIXER_API_URL = 'http://data.fixer.io/api/latest';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly ?string $apiKey = null,
        private readonly string $baseCurrency = 'EUR',
    ) {
    }

    public function fetchRates(): array
    {
        if (!$this->isEnabled()) {
            $this->logger->warning('[Fixer Provider] Provider is disabled (no API key configured)');

            return [];
        }

        try {
            $this->logger->info('[Fixer Provider] Fetching exchange rates from Fixer.io...');

            $response = $this->httpClient->request('GET', self::FIXER_API_URL, [
                'query' => [
                    'access_key' => $this->apiKey,
                    'base' => $this->baseCurrency,
                ],
                'timeout' => 10,
            ]);

            $data = $response->toArray();

            if (!isset($data['success']) || !$data['success']) {
                $errorMessage = $data['error']['info'] ?? 'Unknown error';

                throw new \RuntimeException('Fixer API error: ' . $errorMessage);
            }

            if (!isset($data['rates']) || !is_array($data['rates'])) {
                throw new \RuntimeException('Invalid response format from Fixer API');
            }

            $date = new \DateTimeImmutable($data['date'] ?? 'now');
            $rates = [];

            foreach ($data['rates'] as $targetCurrency => $ratio) {
                $rates[] = new ExchangeRateData(
                    sourceCurrency: $this->baseCurrency,
                    targetCurrency: $targetCurrency,
                    ratio: (float) $ratio,
                    date: $date,
                );
            }

            $this->logger->info(sprintf('[Fixer Provider] Successfully fetched %d exchange rates', count($rates)));

            return $rates;
        } catch (\Exception $e) {
            $this->logger->error(sprintf('[Fixer Provider] Failed to fetch exchange rates: %s', $e->getMessage()));

            throw new \RuntimeException('Fixer Provider failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getName(): string
    {
        return 'Fixer.io';
    }

    public function isEnabled(): bool
    {
        return !empty($this->apiKey);
    }
}
