<?php

declare(strict_types=1);

namespace Adeliom\SyliusExchangeRatePlugin\Service;

use Doctrine\ORM\EntityManagerInterface;
use Adeliom\SyliusExchangeRatePlugin\Provider\ExchangeRateProviderInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Currency\Model\ExchangeRateInterface;
use Sylius\Component\Currency\Repository\CurrencyRepositoryInterface;
use Sylius\Component\Currency\Repository\ExchangeRateRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

/**
 * Service responsible for synchronizing exchange rates from external providers.
 *
 * This service:
 * - Discovers and orchestrates all registered exchange rate providers
 * - Updates or creates ExchangeRate entities in Sylius
 * - Handles errors gracefully and provides detailed logging
 * - Skips rates for currencies that don't exist in the system
 */
final class ExchangeRateSynchronizer
{
    /**
     * @param iterable<ExchangeRateProviderInterface> $providers
     * @param ExchangeRateRepositoryInterface<ExchangeRateInterface> $exchangeRateRepository
     * @param CurrencyRepositoryInterface<CurrencyInterface> $currencyRepository
     */
    public function __construct(
        private readonly iterable $providers,
        private readonly ExchangeRateRepositoryInterface $exchangeRateRepository,
        private readonly CurrencyRepositoryInterface $currencyRepository,
        private readonly FactoryInterface $exchangeRateFactory,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Synchronizes exchange rates from all enabled providers.
     *
     * @return array{
     *     success: bool,
     *     rates_updated: int,
     *     rates_created: int,
     *     errors: array<string>,
     *     providers_used: array<string>
     * }
     */
    public function synchronize(): array
    {
        $this->logger->info('[ExchangeRateSynchronizer] Starting synchronization...');

        $ratesUpdated = 0;
        $ratesCreated = 0;
        $errors = [];
        $providersUsed = [];

        foreach ($this->providers as $provider) {
            if (!$provider->isEnabled()) {
                $this->logger->info(sprintf('[ExchangeRateSynchronizer] Skipping disabled provider: %s', $provider->getName()));

                continue;
            }

            try {
                $this->logger->info(sprintf('[ExchangeRateSynchronizer] Using provider: %s', $provider->getName()));
                $providersUsed[] = $provider->getName();

                $rates = $provider->fetchRates();

                foreach ($rates as $rateData) {
                    try {
                        $result = $this->updateOrCreateExchangeRate(
                            $rateData->sourceCurrency,
                            $rateData->targetCurrency,
                            $rateData->ratio,
                        );

                        if ($result === 'created') {
                            ++$ratesCreated;
                        } elseif ($result === 'updated') {
                            ++$ratesUpdated;
                        }
                    } catch (\Exception $e) {
                        $errorMsg = sprintf(
                            'Failed to update rate %s/%s: %s',
                            $rateData->sourceCurrency,
                            $rateData->targetCurrency,
                            $e->getMessage(),
                        );
                        $this->logger->warning('[ExchangeRateSynchronizer] ' . $errorMsg);
                        $errors[] = $errorMsg;
                    }
                }

                $this->entityManager->flush();
            } catch (\Exception $e) {
                $errorMsg = sprintf('Provider %s failed: %s', $provider->getName(), $e->getMessage());
                $this->logger->error('[ExchangeRateSynchronizer] ' . $errorMsg);
                $errors[] = $errorMsg;
            }
        }

        $this->logger->info(sprintf(
            '[ExchangeRateSynchronizer] Synchronization complete. Created: %d, Updated: %d, Errors: %d',
            $ratesCreated,
            $ratesUpdated,
            count($errors),
        ));

        return [
            'success' => count($errors) === 0 || ($ratesCreated + $ratesUpdated) > 0,
            'rates_updated' => $ratesUpdated,
            'rates_created' => $ratesCreated,
            'errors' => $errors,
            'providers_used' => $providersUsed,
        ];
    }

    /**
     * Updates an existing exchange rate or creates a new one.
     *
     * @return string 'created', 'updated', or 'skipped'
     */
    private function updateOrCreateExchangeRate(
        string $sourceCurrencyCode,
        string $targetCurrencyCode,
        float $ratio,
    ): string {
        // Check if both currencies exist in the system
        $sourceCurrency = $this->currencyRepository->findOneBy(['code' => $sourceCurrencyCode]);
        $targetCurrency = $this->currencyRepository->findOneBy(['code' => $targetCurrencyCode]);

        if (!$sourceCurrency instanceof CurrencyInterface) {
            $this->logger->debug(sprintf('Source currency %s not found in system, skipping', $sourceCurrencyCode));

            return 'skipped';
        }

        if (!$targetCurrency instanceof CurrencyInterface) {
            $this->logger->debug(sprintf('Target currency %s not found in system, skipping', $targetCurrencyCode));

            return 'skipped';
        }

        // Try to find existing exchange rate
        $exchangeRate = $this->exchangeRateRepository->findOneWithCurrencyPair($sourceCurrencyCode, $targetCurrencyCode);

        if ($exchangeRate instanceof ExchangeRateInterface) {
            // Update existing rate
            $exchangeRate->setRatio($ratio);
            $this->logger->debug(sprintf('Updated rate %s/%s = %F', $sourceCurrencyCode, $targetCurrencyCode, $ratio));

            return 'updated';
        }

        // Create new exchange rate
        /** @var ExchangeRateInterface $exchangeRate */
        $exchangeRate = $this->exchangeRateFactory->createNew();
        $exchangeRate->setSourceCurrency($sourceCurrency);
        $exchangeRate->setTargetCurrency($targetCurrency);
        $exchangeRate->setRatio($ratio);

        $this->entityManager->persist($exchangeRate);
        $this->logger->debug(sprintf('Created rate %s/%s = %F', $sourceCurrencyCode, $targetCurrencyCode, $ratio));

        return 'created';
    }

    /**
     * Returns list of available providers with their status.
     *
     * @return array<array{name: string, enabled: bool}>
     */
    public function getAvailableProviders(): array
    {
        $providers = [];

        foreach ($this->providers as $provider) {
            $providers[] = [
                'name' => $provider->getName(),
                'enabled' => $provider->isEnabled(),
            ];
        }

        return $providers;
    }
}
