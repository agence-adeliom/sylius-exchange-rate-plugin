<?php

declare(strict_types=1);

namespace Adeliom\SyliusExchangeRatePlugin\Command;

use Adeliom\SyliusExchangeRatePlugin\Service\ExchangeRateSynchronizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command to synchronize exchange rates from external providers.
 *
 * Usage:
 *   php bin/console adeliom:exchange-rates:synchronize
 */
#[AsCommand(
    name: 'adeliom:exchange-rates:synchronize',
    description: 'Synchronize exchange rates from external providers',
)]
final class SynchronizeExchangeRatesCommand extends Command
{
    public function __construct(
        private readonly ExchangeRateSynchronizer $synchronizer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Exchange Rate Synchronization');

        // Show available providers
        $providers = $this->synchronizer->getAvailableProviders();
        $io->section('Available Providers');

        $providerRows = [];
        foreach ($providers as $provider) {
            $status = $provider['enabled'] ? '<info>✓ Enabled</info>' : '<comment>✗ Disabled</comment>';
            $providerRows[] = [$provider['name'], $status];
        }

        $io->table(['Provider', 'Status'], $providerRows);

        // Perform synchronization
        $io->section('Synchronizing...');

        try {
            $result = $this->synchronizer->synchronize();

            // Display results
            if ($result['success']) {
                $io->success('Synchronization completed successfully!');
            } else {
                $io->warning('Synchronization completed with errors');
            }

            $io->definitionList(
                ['Rates Created' => (string) $result['rates_created']],
                ['Rates Updated' => (string) $result['rates_updated']],
                ['Providers Used' => implode(', ', $result['providers_used'])],
                ['Errors' => (string) count($result['errors'])],
            );

            // Show errors if any
            if (!empty($result['errors'])) {
                $io->section('Errors');
                foreach ($result['errors'] as $error) {
                    $io->error($error);
                }

                return Command::FAILURE;
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Synchronization failed: ' . $e->getMessage());

            if ($output->isVerbose()) {
                $io->block($e->getTraceAsString(), 'TRACE', 'fg=white;bg=red', ' ', true);
            }

            return Command::FAILURE;
        }
    }
}
