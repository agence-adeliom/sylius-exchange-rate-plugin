<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusExchangeRatePlugin\Behat\Page\Admin\ExchangeRate;

use Sylius\Behat\Page\Admin\Crud\IndexPageInterface as BaseIndexPageInterface;

interface IndexPageInterface extends BaseIndexPageInterface
{
    public function clickSynchronizeButton(): void;

    public function hasSyncSuccessMessage(): bool;

    public function getSyncStatusMessage(): string;
}
