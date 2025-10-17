<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusExchangeRatePlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Tests\Adeliom\SyliusExchangeRatePlugin\Behat\Page\Admin\ExchangeRate\IndexPageInterface;
use Webmozart\Assert\Assert;

final class ExchangeRateContext implements Context
{
    public function __construct(
        private IndexPageInterface $indexPage
    ) {
    }

    /**
     * @When I browse exchange rates
     */
    public function iBrowseExchangeRates(): void
    {
        $this->indexPage->open();
    }

    /**
     * @When I click the synchronize button
     */
    public function iClickTheSynchronizeButton(): void
    {
        $this->indexPage->clickSynchronizeButton();
    }

    /**
     * @Then I should see a success message
     */
    public function iShouldSeeASuccessMessage(): void
    {
        Assert::true(
            $this->indexPage->hasSyncSuccessMessage(),
            'Expected to see a success message after synchronization'
        );
    }

    /**
     * @Then the exchange rates should be updated
     */
    public function theExchangeRatesShouldBeUpdated(): void
    {
        $message = $this->indexPage->getSyncStatusMessage();

        Assert::contains(
            $message,
            'Synchronization complete',
            sprintf('Expected success message to contain "Synchronization complete", got "%s"', $message)
        );
    }
}
