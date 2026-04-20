<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api;

use SignDocsBrasil\Api\Resources\DocumentGroupsResource;
use SignDocsBrasil\Api\Resources\DocumentsResource;
use SignDocsBrasil\Api\Resources\EnvelopesResource;
use SignDocsBrasil\Api\Resources\EvidenceResource;
use SignDocsBrasil\Api\Resources\HealthResource;
use SignDocsBrasil\Api\Resources\SigningResource;
use SignDocsBrasil\Api\Resources\SigningSessionsResource;
use SignDocsBrasil\Api\Resources\StepsResource;
use SignDocsBrasil\Api\Resources\TransactionsResource;
use SignDocsBrasil\Api\Resources\UsersResource;
use SignDocsBrasil\Api\Resources\VerificationResource;
use SignDocsBrasil\Api\Resources\WebhooksResource;

/**
 * SignDocsBrasil API client.
 *
 * Usage with client_secret authentication:
 *
 *     $client = new SignDocsBrasilClient(new Config(
 *         clientId: 'your-client-id',
 *         clientSecret: 'your-client-secret',
 *     ));
 *
 * Usage with private_key_jwt (ES256) authentication:
 *
 *     $client = new SignDocsBrasilClient(new Config(
 *         clientId: 'your-client-id',
 *         privateKey: file_get_contents('/path/to/private-key.pem'),
 *         kid: 'your-key-id',
 *     ));
 *
 * Then use the resource properties to interact with the API:
 *
 *     $health = $client->health->check();
 *     $tx = $client->transactions->create($request);
 *     $client->webhooks->register($webhookRequest);
 */
final class SignDocsBrasilClient
{
    public readonly HealthResource $health;
    public readonly TransactionsResource $transactions;
    public readonly DocumentsResource $documents;
    public readonly StepsResource $steps;
    public readonly SigningResource $signing;
    public readonly EvidenceResource $evidence;
    public readonly VerificationResource $verification;
    public readonly UsersResource $users;
    public readonly WebhooksResource $webhooks;
    public readonly DocumentGroupsResource $documentGroups;
    public readonly SigningSessionsResource $signingSessions;
    public readonly EnvelopesResource $envelopes;

    public function __construct(Config $config)
    {
        $auth = new AuthHandler(
            clientId: $config->clientId,
            clientSecret: $config->clientSecret,
            privateKey: $config->privateKey,
            kid: $config->kid,
            baseUrl: $config->baseUrl,
            scopes: $config->scopes,
            cache: $config->tokenCache,
        );

        $http = new HttpClient(
            baseUrl: $config->baseUrl,
            timeout: $config->timeout,
            auth: $auth,
            maxRetries: $config->maxRetries,
            guzzle: $config->guzzle,
            logger: $config->logger,
            onResponse: $config->onResponse,
        );

        $this->health = new HealthResource($http);
        $this->transactions = new TransactionsResource($http);
        $this->documents = new DocumentsResource($http);
        $this->steps = new StepsResource($http);
        $this->signing = new SigningResource($http);
        $this->evidence = new EvidenceResource($http);
        $this->verification = new VerificationResource($http);
        $this->users = new UsersResource($http);
        $this->webhooks = new WebhooksResource($http);
        $this->documentGroups = new DocumentGroupsResource($http);
        $this->signingSessions = new SigningSessionsResource($http);
        $this->envelopes = new EnvelopesResource($http);
    }
}
