<?php

declare(strict_types=1);

namespace Adeliom\SyliusExchangeRatePlugin;

use Adeliom\SyliusExchangeRatePlugin\DependencyInjection\CompilerPass\ExchangeRateProviderPass;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class AdeliomSyliusExchangeRatePlugin extends Bundle
{
    use SyliusPluginTrait;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ExchangeRateProviderPass());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
