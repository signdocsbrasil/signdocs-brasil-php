<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SignDocsBrasil\Api\Config;

final class ConfigTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = new Config(clientId: 'test', clientSecret: 'secret');

        $this->assertSame(Config::DEFAULT_BASE_URL, $config->baseUrl);
        $this->assertSame(Config::DEFAULT_TIMEOUT, $config->timeout);
        $this->assertSame(Config::DEFAULT_MAX_RETRIES, $config->maxRetries);
        $this->assertSame(Config::DEFAULT_SCOPES, $config->scopes);
    }

    public function testCustomValues(): void
    {
        $config = new Config(
            clientId: 'my-client',
            clientSecret: 'my-secret',
            baseUrl: 'https://custom.api.com',
            timeout: 5,
            maxRetries: 2,
            scopes: ['custom:scope'],
        );

        $this->assertSame('https://custom.api.com', $config->baseUrl);
        $this->assertSame(5, $config->timeout);
        $this->assertSame(2, $config->maxRetries);
        $this->assertSame(['custom:scope'], $config->scopes);
    }

    public function testEmptyClientIdThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('clientId is required');

        new Config(clientId: '', clientSecret: 'secret');
    }

    public function testNoAuthThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Either clientSecret or privateKey');

        new Config(clientId: 'test');
    }

    public function testPrivateKeyWithoutKidThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('kid is required');

        new Config(clientId: 'test', privateKey: 'pem-data');
    }

    public function testValidWithPrivateKey(): void
    {
        $config = new Config(clientId: 'test', privateKey: 'pem-data', kid: 'key-1');
        $this->assertSame('pem-data', $config->privateKey);
        $this->assertSame('key-1', $config->kid);
    }

    public function testBaseUrlTrailingSlashTrimmed(): void
    {
        $config = new Config(
            clientId: 'test',
            clientSecret: 'secret',
            baseUrl: 'https://custom.api.com/',
        );

        $this->assertSame('https://custom.api.com', $config->baseUrl);
    }
}
