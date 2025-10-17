<?php

declare(strict_types=1);

namespace Adeliom\SyliusExchangeRatePlugin\Controller\Admin;

use Adeliom\SyliusExchangeRatePlugin\Service\ExchangeRateSynchronizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for synchronizing exchange rates from external providers.
 */
final class SynchronizeExchangeRateController extends AbstractController
{
    public function __construct(
        private readonly ExchangeRateSynchronizer $synchronizer,
    ) {
    }

    /**
     * Synchronizes exchange rates and redirects to index with flash message.
     */
    #[Route(
        path: '/exchange-rates/synchronize',
        name: 'adeliom_admin_exchange_rate_synchronize',
        methods: ['POST'],
    )]
    public function __invoke(Request $request): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('exchange_rate_synchronize', (string) $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('sylius_admin_exchange_rate_index');
        }

        try {
            $result = $this->synchronizer->synchronize();

            $message = sprintf(
                'Synchronization complete! Created: %d, Updated: %d',
                $result['rates_created'],
                $result['rates_updated'],
            );

            if (!empty($result['errors'])) {
                $message .= sprintf(' (with %d errors)', count($result['errors']));
                $this->addFlash('warning', $message);
            } else {
                $this->addFlash('success', $message);
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Synchronization failed: ' . $e->getMessage());
        }

        return $this->redirectToRoute('sylius_admin_exchange_rate_index');
    }
}
