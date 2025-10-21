<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusExchangeRatePlugin\Behat\Page\Admin\ExchangeRate;

use Behat\Mink\Element\NodeElement;
use Sylius\Behat\Page\Admin\Crud\IndexPage as BaseIndexPage;

final class IndexPage extends BaseIndexPage implements IndexPageInterface
{
    public function clickSynchronizeButton(): void
    {
        // Find the button that triggers the dialog
        $triggerButton = $this->getDocument()->find('css', '#dialog-synchronize-exchange-rates-trigger');

        if (null === $triggerButton) {
            throw new \RuntimeException('Synchronize trigger button not found on the page');
        }

        $triggerButton->click();

        // Wait for the dialog to appear and be open
        $this->getDocument()->waitFor(10, function () {
            $dialog = $this->getDocument()->find('css', '#dialog-synchronize-exchange-rates[open]');
            if (!$dialog) {
                return false;
            }

            // Check that the confirm button is visible
            $confirmButton = $this->getDocument()->find('css', '[data-test-confirm-button]');
            return $confirmButton && $confirmButton->isVisible();
        });

        // Small wait for dialog animation
        usleep(300000); // 300ms

        // Find and submit the form
        $form = $this->getDocument()->find('css', '#dialog-synchronize-exchange-rates form');

        if (null === $form) {
            throw new \RuntimeException('Form not found in the dialog');
        }

        // Submit the form
        $form->submit();

        // Wait for the page to reload or flash message to appear
        $this->getDocument()->waitFor(10, function () {
            $flashMessages = $this->getDocument()->find('css', '.alert, .flash-message');
            return null !== $flashMessages;
        });
    }

    public function hasSyncSuccessMessage(): bool
    {
        // Check for Sylius success flash message
        $successAlert = $this->getDocument()->find('css', '.alert-success, .flash-success');

        if (null === $successAlert || !$successAlert->isVisible()) {
            return false;
        }

        $text = $successAlert->getText();

        // Check if the message contains synchronization confirmation
        return str_contains($text, 'Synchronization complete');
    }

    public function getSyncStatusMessage(): string
    {
        // Look for any flash message (success, warning, or error)
        $alert = $this->getDocument()->find('css', '.alert, .flash-message');

        if (null === $alert) {
            throw new \RuntimeException('No flash message found');
        }

        return trim($alert->getText());
    }
}
