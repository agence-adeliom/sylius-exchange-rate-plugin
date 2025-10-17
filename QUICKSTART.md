# Quick Start Guide

This guide will help you get started with the Sylius Exchange Rate Plugin in minutes.

## Installation (Development Environment)

### Prerequisites

Make sure you have:
- PHP 8.2+
- Composer
- Symfony CLI (optional, but recommended)
- MySQL/PostgreSQL

### Step 1: Clone and Install

```bash
cd /path/to/your/plugin
composer install
```

### Step 2: Configure Database

Edit `tests/TestApplication/.env`:

```env
DATABASE_URL=mysql://db:db@db/acme_sylius_exchange_rate_%kernel.environment%
```

Or for local development:

```env
DATABASE_URL=mysql://root:password@127.0.0.1:3306/acme_sylius_exchange_rate_dev
```

### Step 3: Initialize Database

```bash
# Using composer scripts
composer run database-reset

# Or manually
vendor/bin/console doctrine:database:create
vendor/bin/console doctrine:migrations:migrate -n
vendor/bin/console sylius:fixtures:load -n
```

### Step 4: Install Frontend Assets

```bash
composer run frontend-clear
```

### Step 5: Start the Server

```bash
symfony server:start -d
```

Or with Docker:

```bash
make init
make database-init
make up
```

### Step 6: Access the Application

Open your browser:
- Frontend: https://localhost:8000 (or http://localhost for Docker)
- Admin: https://localhost:8000/admin
  - Username: `sylius@example.com`
  - Password: `sylius`

### Step 7: Test Exchange Rate Synchronization

1. Log in to the admin panel
2. Go to **Configuration** → **Exchange Rates**
3. First, add some currencies:
   - Go to **Configuration** → **Currencies**
   - Add currencies like USD, EUR, GBP, JPY, etc.
4. Return to **Exchange Rates**
5. Click the **"Synchronize Exchange Rates"** button
6. Watch the magic happen!

## Testing the Plugin

### Run Tests

```bash
vendor/bin/phpunit
vendor/bin/behat --strict --tags="~@javascript&&~@mink:chromedriver"
```

### Check Code Quality

```bash
vendor/bin/phpstan analyse
vendor/bin/ecs check
```

## Configuration Options

### Using Fixer.io Provider

1. Get a free API key from https://fixer.io
2. Add to your `.env` file:

```env
FIXER_API_KEY=your_api_key_here
FIXER_BASE_CURRENCY=EUR
```

3. Clear cache and synchronize!

### Using ECB Provider Only

ECB provider is enabled by default and requires no configuration. It provides EUR-based rates for major currencies.

## Troubleshooting

### "Button doesn't appear"

Make sure you've:
1. Imported routes in `config/routes.yaml`
2. Imported Twig hooks in `config/packages/twig_hooks.yaml`
3. Cleared cache: `php bin/console cache:clear`

### "No rates are synchronized"

1. Check that currencies exist in your system
2. Review logs: `var/log/dev.log` or `var/log/prod.log`
3. Test provider manually (see below)

### Manual Testing

You can test providers manually with PHP:

```php
// In Symfony console or controller
$provider = $container->get(Acme\SyliusExchangeRatePlugin\Provider\EcbProvider::class);
$rates = $provider->fetchRates();
var_dump($rates);
```

## Next Steps

- [Read the full README](README.md) for detailed documentation
- [Create your own provider](README.md#creating-your-own-provider)
- Explore the codebase to understand the architecture
- Write tests for your custom providers
- Contribute improvements back to the project!

## Useful Commands

```bash
# Database management
composer run database-reset        # Reset database with fixtures
vendor/bin/console doctrine:database:drop --force
vendor/bin/console doctrine:database:create

# Frontend
composer run frontend-clear        # Rebuild frontend assets

# Cache
php bin/console cache:clear        # Clear Symfony cache

# Testing
vendor/bin/phpunit                 # Unit tests
vendor/bin/behat                   # Feature tests
vendor/bin/phpstan analyse         # Static analysis
vendor/bin/ecs check              # Coding standards

# Docker
make init                          # Initialize Docker environment
make up                            # Start containers
make down                          # Stop containers
make database-init                 # Initialize database in Docker
```

## Support

For issues, questions, or contributions:
- Check the [README](README.md)
- Review existing GitHub issues
- Create a new issue with details about your problem
- Join the Sylius community for general Sylius questions

Happy coding! 🚀
