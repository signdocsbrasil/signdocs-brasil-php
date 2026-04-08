<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api\Tests;

use PHPUnit\Framework\TestCase;
use SignDocsBrasil\Api\Config;
use SignDocsBrasil\Api\Resources\DocumentGroupsResource;
use SignDocsBrasil\Api\Resources\DocumentsResource;
use SignDocsBrasil\Api\Resources\EvidenceResource;
use SignDocsBrasil\Api\Resources\HealthResource;
use SignDocsBrasil\Api\Resources\SigningResource;
use SignDocsBrasil\Api\Resources\StepsResource;
use SignDocsBrasil\Api\Resources\TransactionsResource;
use SignDocsBrasil\Api\Resources\UsersResource;
use SignDocsBrasil\Api\Resources\VerificationResource;
use SignDocsBrasil\Api\Resources\WebhooksResource;
use SignDocsBrasil\Api\SignDocsBrasilClient;

final class SignDocsBrasilClientTest extends TestCase
{
    private SignDocsBrasilClient $client;

    protected function setUp(): void
    {
        $this->client = new SignDocsBrasilClient(new Config(
            clientId: 'test-client',
            clientSecret: 'test-secret',
        ));
    }

    public function testHealthResourceInitialized(): void
    {
        $this->assertInstanceOf(HealthResource::class, $this->client->health);
    }

    public function testTransactionsResourceInitialized(): void
    {
        $this->assertInstanceOf(TransactionsResource::class, $this->client->transactions);
    }

    public function testDocumentsResourceInitialized(): void
    {
        $this->assertInstanceOf(DocumentsResource::class, $this->client->documents);
    }

    public function testStepsResourceInitialized(): void
    {
        $this->assertInstanceOf(StepsResource::class, $this->client->steps);
    }

    public function testSigningResourceInitialized(): void
    {
        $this->assertInstanceOf(SigningResource::class, $this->client->signing);
    }

    public function testEvidenceResourceInitialized(): void
    {
        $this->assertInstanceOf(EvidenceResource::class, $this->client->evidence);
    }

    public function testVerificationResourceInitialized(): void
    {
        $this->assertInstanceOf(VerificationResource::class, $this->client->verification);
    }

    public function testUsersResourceInitialized(): void
    {
        $this->assertInstanceOf(UsersResource::class, $this->client->users);
    }

    public function testWebhooksResourceInitialized(): void
    {
        $this->assertInstanceOf(WebhooksResource::class, $this->client->webhooks);
    }

    public function testDocumentGroupsResourceInitialized(): void
    {
        $this->assertInstanceOf(DocumentGroupsResource::class, $this->client->documentGroups);
    }

    public function testClientWithPrivateKeyAuth(): void
    {
        $client = new SignDocsBrasilClient(new Config(
            clientId: 'test-client',
            privateKey: '-----BEGIN EC PRIVATE KEY-----\nfake\n-----END EC PRIVATE KEY-----',
            kid: 'key-1',
        ));

        $this->assertInstanceOf(TransactionsResource::class, $client->transactions);
        $this->assertInstanceOf(HealthResource::class, $client->health);
    }

    public function testClientWithCustomConfig(): void
    {
        $client = new SignDocsBrasilClient(new Config(
            clientId: 'custom-client',
            clientSecret: 'custom-secret',
            baseUrl: 'https://staging-api.signdocs.com.br',
            timeout: 60,
            maxRetries: 10,
            scopes: ['transactions:read'],
        ));

        $this->assertInstanceOf(TransactionsResource::class, $client->transactions);
    }
}
