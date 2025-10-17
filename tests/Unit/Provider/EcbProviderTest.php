<?php

declare(strict_types=1);

namespace Tests\Adeliom\SyliusExchangeRatePlugin\Unit\Provider;

use Adeliom\SyliusExchangeRatePlugin\Provider\EcbProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Unit test example for ECB Provider.
 *
 * This demonstrates how to test exchange rate providers.
 */
final class EcbProviderTest extends TestCase
{
    private const MOCK_ECB_XML = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<gesmes:Envelope xmlns:gesmes="http://www.gesmes.org/xml/2002-08-01" xmlns="http://www.ecb.int/vocabulary/2002-08-01/eurofxref">
    <Cube>
        <Cube time="2024-10-17">
            <Cube currency="USD" rate="1.0845"/>
            <Cube currency="JPY" rate="162.35"/>
            <Cube currency="GBP" rate="0.8340"/>
        </Cube>
    </Cube>
</gesmes:Envelope>
XML;

    public function testFetchRatesReturnsCorrectData(): void
    {
        // Arrange
        $mockClient = new MockHttpClient([
            new MockResponse(self::MOCK_ECB_XML),
        ]);

        $provider = new EcbProvider($mockClient, new NullLogger());

        // Act
        $rates = $provider->fetchRates();

        // Assert
        $this->assertCount(3, $rates);

        $usdRate = $rates[0];
        $this->assertEquals('EUR', $usdRate->sourceCurrency);
        $this->assertEquals('USD', $usdRate->targetCurrency);
        $this->assertEquals(1.0845, $usdRate->ratio);

        $jpyRate = $rates[1];
        $this->assertEquals('JPY', $jpyRate->targetCurrency);
        $this->assertEquals(162.35, $jpyRate->ratio);

        $gbpRate = $rates[2];
        $this->assertEquals('GBP', $gbpRate->targetCurrency);
        $this->assertEquals(0.8340, $gbpRate->ratio);
    }

    public function testGetNameReturnsCorrectName(): void
    {
        // Arrange
        $mockClient = new MockHttpClient([]);
        $provider = new EcbProvider($mockClient, new NullLogger());

        // Act
        $name = $provider->getName();

        // Assert
        $this->assertEquals('ECB (European Central Bank)', $name);
    }

    public function testIsEnabledAlwaysReturnsTrue(): void
    {
        // Arrange
        $mockClient = new MockHttpClient([]);
        $provider = new EcbProvider($mockClient, new NullLogger());

        // Act
        $isEnabled = $provider->isEnabled();

        // Assert
        $this->assertTrue($isEnabled);
    }

    public function testFetchRatesThrowsExceptionOnInvalidXml(): void
    {
        // Arrange
        $mockClient = new MockHttpClient([
            new MockResponse('invalid xml'),
        ]);

        $provider = new EcbProvider($mockClient, new NullLogger());

        // Assert
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ECB Provider failed');

        // Act
        $provider->fetchRates();
    }

    public function testFetchRatesThrowsExceptionOnHttpError(): void
    {
        // Arrange
        $mockClient = new MockHttpClient([
            new MockResponse('', ['http_code' => 500]),
        ]);

        $provider = new EcbProvider($mockClient, new NullLogger());

        // Assert
        $this->expectException(\RuntimeException::class);

        // Act
        $provider->fetchRates();
    }
}
