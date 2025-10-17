<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusExchangeRatePlugin\Unit\Provider;

use Adeliom\SyliusExchangeRatePlugin\Provider\FixerProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Unit test for Fixer.io Provider.
 *
 * This demonstrates how to test exchange rate providers with API keys.
 */
final class FixerProviderTest extends TestCase
{
    private const MOCK_FIXER_JSON = <<<JSON
{
    "success": true,
    "timestamp": 1729177200,
    "base": "EUR",
    "date": "2024-10-17",
    "rates": {
        "USD": 1.0845,
        "JPY": 162.35,
        "GBP": 0.8340,
        "CHF": 0.9356
    }
}
JSON;

    private const MOCK_FIXER_ERROR_JSON = <<<JSON
{
    "success": false,
    "error": {
        "code": 101,
        "info": "Invalid API key"
    }
}
JSON;

    public function testFetchRatesReturnsCorrectData(): void
    {
        // Arrange
        $mockClient = new MockHttpClient([
            new MockResponse(self::MOCK_FIXER_JSON),
        ]);

        $provider = new FixerProvider(
            $mockClient,
            new NullLogger(),
            'test-api-key',
            'EUR'
        );

        // Act
        $rates = $provider->fetchRates();

        // Assert
        $this->assertCount(4, $rates);

        $usdRate = $rates[0];
        $this->assertEquals('EUR', $usdRate->sourceCurrency);
        $this->assertEquals('USD', $usdRate->targetCurrency);
        $this->assertEquals(1.0845, $usdRate->ratio);
        $this->assertEquals('2024-10-17', $usdRate->date->format('Y-m-d'));

        $jpyRate = $rates[1];
        $this->assertEquals('JPY', $jpyRate->targetCurrency);
        $this->assertEquals(162.35, $jpyRate->ratio);

        $gbpRate = $rates[2];
        $this->assertEquals('GBP', $gbpRate->targetCurrency);
        $this->assertEquals(0.8340, $gbpRate->ratio);

        $chfRate = $rates[3];
        $this->assertEquals('CHF', $chfRate->targetCurrency);
        $this->assertEquals(0.9356, $chfRate->ratio);
    }

    public function testFetchRatesWithCustomBaseCurrency(): void
    {
        // Arrange
        $customJson = <<<JSON
{
    "success": true,
    "timestamp": 1729177200,
    "base": "USD",
    "date": "2024-10-17",
    "rates": {
        "EUR": 0.9221,
        "JPY": 149.72
    }
}
JSON;

        $mockClient = new MockHttpClient([
            new MockResponse($customJson),
        ]);

        $provider = new FixerProvider(
            $mockClient,
            new NullLogger(),
            'test-api-key',
            'USD'
        );

        // Act
        $rates = $provider->fetchRates();

        // Assert
        $this->assertCount(2, $rates);
        $this->assertEquals('USD', $rates[0]->sourceCurrency);
        $this->assertEquals('EUR', $rates[0]->targetCurrency);
    }

    public function testGetNameReturnsCorrectName(): void
    {
        // Arrange
        $mockClient = new MockHttpClient([]);
        $provider = new FixerProvider($mockClient, new NullLogger(), 'test-api-key');

        // Act
        $name = $provider->getName();

        // Assert
        $this->assertEquals('Fixer.io', $name);
    }

    public function testIsEnabledReturnsTrueWithApiKey(): void
    {
        // Arrange
        $mockClient = new MockHttpClient([]);
        $provider = new FixerProvider($mockClient, new NullLogger(), 'test-api-key');

        // Act
        $isEnabled = $provider->isEnabled();

        // Assert
        $this->assertTrue($isEnabled);
    }

    public function testIsEnabledReturnsFalseWithoutApiKey(): void
    {
        // Arrange
        $mockClient = new MockHttpClient([]);
        $provider = new FixerProvider($mockClient, new NullLogger(), null);

        // Act
        $isEnabled = $provider->isEnabled();

        // Assert
        $this->assertFalse($isEnabled);
    }

    public function testIsEnabledReturnsFalseWithEmptyApiKey(): void
    {
        // Arrange
        $mockClient = new MockHttpClient([]);
        $provider = new FixerProvider($mockClient, new NullLogger(), '');

        // Act
        $isEnabled = $provider->isEnabled();

        // Assert
        $this->assertFalse($isEnabled);
    }

    public function testFetchRatesReturnsEmptyArrayWhenDisabled(): void
    {
        // Arrange
        $mockClient = new MockHttpClient([]);
        $provider = new FixerProvider($mockClient, new NullLogger(), null);

        // Act
        $rates = $provider->fetchRates();

        // Assert
        $this->assertIsArray($rates);
        $this->assertEmpty($rates);
    }

    public function testFetchRatesThrowsExceptionOnApiError(): void
    {
        // Arrange
        $mockClient = new MockHttpClient([
            new MockResponse(self::MOCK_FIXER_ERROR_JSON),
        ]);

        $provider = new FixerProvider($mockClient, new NullLogger(), 'invalid-key');

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Fixer Provider failed: Fixer API error: Invalid API key');

        // Act
        $provider->fetchRates();
    }

    public function testFetchRatesThrowsExceptionOnInvalidJsonFormat(): void
    {
        // Arrange
        $invalidJson = <<<JSON
{
    "success": true,
    "date": "2024-10-17"
}
JSON;

        $mockClient = new MockHttpClient([
            new MockResponse($invalidJson),
        ]);

        $provider = new FixerProvider($mockClient, new NullLogger(), 'test-api-key');

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid response format from Fixer API');

        // Act
        $provider->fetchRates();
    }

    public function testFetchRatesThrowsExceptionOnHttpError(): void
    {
        // Arrange
        $mockClient = new MockHttpClient([
            new MockResponse('Service Unavailable', ['http_code' => 503]),
        ]);

        $provider = new FixerProvider($mockClient, new NullLogger(), 'test-api-key');

        // Assert
        $this->expectException(\RuntimeException::class);

        // Act
        $provider->fetchRates();
    }

    public function testFetchRatesThrowsExceptionOnMalformedJson(): void
    {
        // Arrange
        $mockClient = new MockHttpClient([
            new MockResponse('{ invalid json }'),
        ]);

        $provider = new FixerProvider($mockClient, new NullLogger(), 'test-api-key');

        // Assert
        $this->expectException(\RuntimeException::class);

        // Act
        $provider->fetchRates();
    }
}
