# Sylius Exchange Rate Plugin

A Sylius plugin that provides automatic exchange rate synchronization from external APIs with an extensible provider system.

## Features

- Automatic exchange rate synchronization from external APIs
- Extensible provider architecture - easily add your own providers
- Built-in providers:
  - **ECB (European Central Bank)** - Free, no API key required
  - **Fixer.io** - Requires API key (free tier available)
- Admin interface with one-click synchronization button
- Automatic provider discovery via Symfony tags
- Priority-based provider system
- Comprehensive logging
- Error handling with detailed feedback

## Requirements

- PHP 8.2 or higher
- Sylius 2.0 or higher
- Symfony 6.4 or higher

## Installation

### 1. Install the plugin via Composer

```bash
composer require acme/sylius-exchange-rate-plugin
```

### 2. Enable the plugin

The plugin should be automatically enabled in `config/bundles.php`:

```php
return [
    // ...
    Acme\SyliusExchangeRatePlugin\AcmeSyliusExchangeRatePlugin::class => ['all' => true],
];
```

### 3. Import routes

Add the following to `config/routes.yaml`:

```yaml
acme_sylius_exchange_rate_admin:
    resource: '@AcmeSyliusExchangeRatePlugin/config/routes/admin.yaml'
    prefix: /admin
```

### 4. Import Twig hooks (for Sylius 2.0+)

Add to `config/packages/twig_hooks.yaml`:

```yaml
imports:
    - { resource: '@AcmeSyliusExchangeRatePlugin/config/twig_hooks/admin.yaml' }
```

### 5. Configure environment variables

Copy `.env.example` to your `.env` file and configure:

```bash
# .env
FIXER_API_KEY=your_api_key_here  # Leave empty to use only ECB provider
FIXER_BASE_CURRENCY=EUR
```

### 6. Clear cache

```bash
php bin/console cache:clear
```

## Usage

### Admin Interface

1. Navigate to **Exchange Rates** in your Sylius admin panel (`/admin/exchange-rates`)
2. Click the **"Synchronize Exchange Rates"** button
3. The plugin will fetch rates from all enabled providers and update your exchange rates
4. A success/error message will be displayed, and the page will reload

### Available Providers

#### ECB (European Central Bank)

- **Status**: Always enabled
- **API Key**: Not required
- **Base Currency**: EUR
- **Free**: Yes
- **Documentation**: https://www.ecb.europa.eu/stats/eurofxref/

#### Fixer.io

- **Status**: Enabled when `FIXER_API_KEY` is set
- **API Key**: Required (get it at https://fixer.io)
- **Base Currency**: EUR (configurable, but free tier only supports EUR)
- **Free Tier**: 100 requests/month
- **Documentation**: https://fixer.io/documentation

## Creating Your Own Provider

The plugin makes it extremely easy to add your own exchange rate provider. Here's how:

### Step 1: Create a Provider Class

Create a new class implementing `ExchangeRateProviderInterface`:

```php
<?php

declare(strict_types=1);

namespace App\ExchangeRate\Provider;

use Acme\SyliusExchangeRatePlugin\Provider\ExchangeRateProviderInterface;
use Acme\SyliusExchangeRatePlugin\Provider\DTO\ExchangeRateData;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class MyCustomProvider implements ExchangeRateProviderInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly ?string $apiKey = null,
    ) {
    }

    public function fetchRates(): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        try {
            $this->logger->info('[MyCustomProvider] Fetching rates...');

            // Fetch data from your API
            $response = $this->httpClient->request('GET', 'https://api.example.com/rates', [
                'query' => ['api_key' => $this->apiKey],
                'timeout' => 10,
            ]);

            $data = $response->toArray();
            $rates = [];

            // Transform your API response into ExchangeRateData objects
            foreach ($data['rates'] as $currency => $rate) {
                $rates[] = new ExchangeRateData(
                    sourceCurrency: 'USD',
                    targetCurrency: $currency,
                    ratio: (float) $rate,
                    date: new \DateTimeImmutable(),
                );
            }

            return $rates;
        } catch (\Exception $e) {
            $this->logger->error('[MyCustomProvider] Error: ' . $e->getMessage());
            throw new \RuntimeException('MyCustomProvider failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getName(): string
    {
        return 'My Custom Provider';
    }

    public function isEnabled(): bool
    {
        return !empty($this->apiKey);
    }
}
```

### Step 2: Register as a Service

Add your provider to `config/services.yaml`:

```yaml
services:
    App\ExchangeRate\Provider\MyCustomProvider:
        arguments:
            $apiKey: '%env(default::MY_CUSTOM_API_KEY)%'
        tags:
            - { name: 'acme.exchange_rate_provider', priority: 80 }
```

### Step 3: Configure (Optional)

Add environment variables to `.env`:

```bash
MY_CUSTOM_API_KEY=your_api_key
```

**That's it!** Your provider will be automatically discovered and used during synchronization.

### Provider Priority

Providers are executed in order of priority (higher first). Default priorities:
- ECB: 100
- Fixer: 90
- Your custom providers: Set as needed

If multiple providers return rates for the same currency pair, the **last one** wins (lower priority overwrites higher priority).

## Architecture

### Core Components

```
src/
├── Provider/
│   ├── ExchangeRateProviderInterface.php    # Interface for all providers
│   ├── DTO/
│   │   └── ExchangeRateData.php             # Normalized data structure
│   ├── EcbProvider.php                       # ECB implementation
│   └── FixerProvider.php                     # Fixer.io implementation
├── Service/
│   └── ExchangeRateSynchronizer.php          # Orchestrates synchronization
├── Controller/
│   └── Admin/
│       └── SynchronizeExchangeRateController.php  # AJAX endpoint
└── DependencyInjection/
    └── CompilerPass/
        └── ExchangeRateProviderPass.php      # Auto-discovery of providers
```

### How It Works

1. **Provider Registration**: All services tagged with `acme.exchange_rate_provider` are automatically discovered by the `ExchangeRateProviderPass` compiler pass
2. **Synchronization**: The `ExchangeRateSynchronizer` service:
   - Iterates through all enabled providers (by priority)
   - Calls `fetchRates()` on each provider
   - Updates or creates `ExchangeRate` entities in Sylius
   - Skips currencies that don't exist in your system
3. **UI Integration**: A Twig hook injects a synchronization button into the admin interface
4. **AJAX Request**: Clicking the button triggers an AJAX POST to the controller
5. **Response**: JSON response with detailed results and auto-reload on success

## Configuration

### Provider Configuration

Providers are configured via environment variables. See `.env.example` for available options.

### Sylius Configuration

No additional Sylius configuration is required. The plugin automatically:
- Uses Sylius repositories and factories
- Respects existing currencies in your system
- Updates exchange rates safely via Doctrine

## Logging

All synchronization operations are logged. Check your application logs for:
- Provider status (enabled/disabled)
- API requests and responses
- Rates updated/created
- Errors and warnings

Example log output:
```
[info] [ExchangeRateSynchronizer] Starting synchronization...
[info] [ECB Provider] Fetching exchange rates from ECB...
[info] [ECB Provider] Successfully fetched 31 exchange rates
[info] [ExchangeRateSynchronizer] Synchronization complete. Created: 15, Updated: 16, Errors: 0
```

## Troubleshooting

### Button Not Appearing

1. Make sure Twig hooks are imported in `config/packages/twig_hooks.yaml`
2. Clear cache: `php bin/console cache:clear`
3. Check if routes are imported in `config/routes.yaml`

### Provider Not Working

1. Check environment variables are set correctly
2. Check provider is enabled: `$provider->isEnabled()` returns true
3. Review logs for detailed error messages
4. Verify API key is valid

### Rates Not Updating

1. Ensure currencies exist in Sylius (Admin → Currencies)
2. Check database connection
3. Review logs for skipped currencies
4. Verify provider is returning data

## Testing

### Run PHPUnit Tests

```bash
vendor/bin/phpunit
```

### Run Behat Tests

```bash
vendor/bin/behat
```

### Run Code Quality Checks

```bash
vendor/bin/phpstan analyse
vendor/bin/ecs check
```

## Development

### Using Docker

```bash
make init          # Initialize environment
make up            # Start containers
make database-init # Setup database
make phpunit       # Run tests
```

### Traditional Setup

```bash
composer install
symfony server:start -d
vendor/bin/phpunit
```

## Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Write tests for your changes
4. Ensure code quality (PHPStan, ECS)
5. Submit a pull request

## License

This plugin is licensed under the MIT License.

## Credits

- Developed with Sylius Plugin Skeleton
- Uses Sylius 2.0 best practices
- Follows Symfony coding standards

## Support

For issues and feature requests, please use the GitHub issue tracker.
