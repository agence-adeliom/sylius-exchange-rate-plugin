<?php

declare(strict_types=1);

namespace Adeliom\SyliusExchangeRatePlugin\DependencyInjection\CompilerPass;

use Adeliom\SyliusExchangeRatePlugin\Service\ExchangeRateSynchronizer;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass to automatically discover and register exchange rate providers.
 *
 * This pass collects all services tagged with 'adeliom.exchange_rate_provider'
 * and injects them into the ExchangeRateSynchronizer service.
 */
final class ExchangeRateProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(ExchangeRateSynchronizer::class)) {
            return;
        }

        $definition = $container->findDefinition(ExchangeRateSynchronizer::class);
        $taggedServices = $container->findTaggedServiceIds('adeliom.exchange_rate_provider');

        $providers = [];
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $priority = $attributes['priority'] ?? 0;
                $providers[$priority][] = new Reference($id);
            }
        }

        // Sort providers by priority (higher first)
        krsort($providers);
        $providers = array_merge(...$providers);

        $definition->setArgument('$providers', $providers);
    }
}
