<?php

declare(strict_types=1);

namespace Adeliom\SyliusExchangeRatePlugin\Provider;

use Adeliom\SyliusExchangeRatePlugin\Provider\DTO\ExchangeRateData;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * European Central Bank exchange rate provider.
 *
 * Fetches daily exchange rates from the ECB's free XML feed.
 * No API key required.
 *
 * @see https://www.ecb.europa.eu/stats/eurofxref/
 */
final class EcbProvider implements ExchangeRateProviderInterface
{
    private const ECB_API_URL = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

    private const BASE_CURRENCY = 'EUR';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function fetchRates(): array
    {
        try {
            $this->logger->info('[ECB Provider] Fetching exchange rates from ECB...');

            $response = $this->httpClient->request('GET', self::ECB_API_URL, [
                'timeout' => 10,
            ]);

            $xmlContent = $response->getContent();

            // Suppress XML warnings and use libxml error handling
            libxml_use_internal_errors(true);

            try {
                $xml = new \SimpleXMLElement($xmlContent);
            } catch (\Exception $e) {
                libxml_clear_errors();

                throw $e;
            } finally {
                libxml_use_internal_errors(false);
            }

            // Register namespaces
            $xml->registerXPathNamespace('gesmes', 'http://www.gesmes.org/xml/2002-08-01');
            $xml->registerXPathNamespace('ecb', 'http://www.ecb.int/vocabulary/2002-08-01/eurofxref');

            // Get the date from the parent Cube with time attribute
            $dateCubes = $xml->xpath('//ecb:Cube[@time]');

            if (empty($dateCubes)) {
                $this->logger->error('[ECB Provider] Could not find date cube in XML');

                throw new \RuntimeException('Could not extract date from ECB response');
            }

            $date = new \DateTimeImmutable((string) $dateCubes[0]['time']);

            // Get all currency rate cubes
            $rateCubes = $xml->xpath('//ecb:Cube[@currency][@rate]');

            if (empty($rateCubes)) {
                $this->logger->error('[ECB Provider] Could not find rate cubes in XML');

                throw new \RuntimeException('No exchange rates found in ECB response');
            }

            $rates = [];
            foreach ($rateCubes as $cube) {
                $targetCurrency = (string) $cube['currency'];
                $ratio = (float) $cube['rate'];

                $rates[] = new ExchangeRateData(
                    sourceCurrency: self::BASE_CURRENCY,
                    targetCurrency: $targetCurrency,
                    ratio: $ratio,
                    date: $date,
                );
            }

            $this->logger->info(sprintf('[ECB Provider] Successfully fetched %d exchange rates', count($rates)));

            return $rates;
        } catch (\Exception $e) {
            $this->logger->error(sprintf('[ECB Provider] Failed to fetch exchange rates: %s', $e->getMessage()));

            throw new \RuntimeException('ECB Provider failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getName(): string
    {
        return 'ECB (European Central Bank)';
    }

    public function isEnabled(): bool
    {
        // ECB provider is always enabled as it requires no configuration
        return true;
    }
}
