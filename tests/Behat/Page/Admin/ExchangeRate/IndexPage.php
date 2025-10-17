<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusExchangeRatePlugin\Behat\Page\Admin\ExchangeRate;

use Behat\Mink\Element\NodeElement;
use Sylius\Behat\Page\Admin\Crud\IndexPage as BaseIndexPage;

final class IndexPage extends BaseIndexPage implements IndexPageInterface
{
    public function clickSynchronizeButton(): void
    {
        $button = $this->getDocument()->find('css', '#exchange-rate-sync-button');

        if (null === $button) {
            throw new \RuntimeException('Synchronize button not found on the page');
        }

        $button->click();

        // Wait for the AJAX request to complete
        $this->getDocument()->waitFor(5, function () {
            $statusSpan = $this->getDocument()->find('css', '#sync-status');
            return $statusSpan && $statusSpan->isVisible();
        });
    }

    public function hasSyncSuccessMessage(): bool
    {
        $statusSpan = $this->getDocument()->find('css', '#sync-status');

        if (null === $statusSpan || !$statusSpan->isVisible()) {
            return false;
        }

        $successMessage = $statusSpan->find('css', 'span[style*="color: #28a745"]');

        return null !== $successMessage;
    }

    public function getSyncStatusMessage(): string
    {
        $statusSpan = $this->getDocument()->find('css', '#sync-status');

        if (null === $statusSpan) {
            throw new \RuntimeException('Sync status element not found');
        }

        return trim($statusSpan->getText());
    }
}
