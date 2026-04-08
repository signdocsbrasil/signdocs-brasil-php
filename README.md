# signdocs-brasil-php

SDK oficial em PHP para a API SignDocsBrasil.

## Requisitos

- PHP 8.1+
- Dependências: `guzzlehttp/guzzle`, `firebase/php-jwt`

## Instalação

```bash
composer require signdocs-brasil/signdocs-brasil-php
```

## Início Rápido

```php
use SignDocsBrasil\Api\SignDocsBrasilClient;
use SignDocsBrasil\Api\Config;
use SignDocsBrasil\Api\Models\CreateTransactionRequest;
use SignDocsBrasil\Api\Models\Policy;
use SignDocsBrasil\Api\Models\Signer;

$client = new SignDocsBrasilClient(new Config(
    clientId: 'seu_client_id',
    clientSecret: 'seu_client_secret',
));

$tx = $client->transactions->create(new CreateTransactionRequest(
    purpose: 'DOCUMENT_SIGNATURE',
    policy: new Policy(profile: 'CLICK_ONLY'),
    signer: new Signer(
        name: 'João Silva',
        email: 'joao@example.com',
        userExternalId: 'user-001',
    ),
    document: ['content' => $pdfBase64, 'filename' => 'contrato.pdf'],
));

echo $tx->transactionId . ' ' . $tx->status;
```

### Private Key JWT (ES256)

```php
$client = new SignDocsBrasilClient(new Config(
    clientId: 'seu_client_id',
    privateKey: file_get_contents('./private-key.pem'),
    kid: 'seu-key-id',
));
```

## Recursos Disponíveis

| Recurso | Métodos |
|---------|---------|
| `$client->transactions` | `create`, `list`, `get`, `cancel`, `finalize`, `listAutoPaginate` |
| `$client->documents` | `upload`, `presign`, `confirm`, `download` |
| `$client->steps` | `list`, `start`, `complete` |
| `$client->signing` | `prepare`, `complete` |
| `$client->evidence` | `get` |
| `$client->verification` | `verify`, `downloads` |
| `$client->users` | `enroll` |
| `$client->webhooks` | `register`, `list`, `delete`, `test` |
| `$client->signingSessions` | `create`, `getStatus`, `cancel`, `list`, `waitForCompletion` |
| `$client->envelopes` | `create`, `get`, `addSession`, `combinedStamp` |
| `$client->documentGroups` | `combinedStamp` |
| `$client->health` | `check`, `history` |

## Envelopes (Múltiplos Signatários)

```php
use SignDocsBrasil\Api\Models\CreateEnvelopeRequest;
use SignDocsBrasil\Api\Models\AddEnvelopeSessionRequest;

$envelope = $client->envelopes->create(new CreateEnvelopeRequest(
    signingMode: 'PARALLEL',
    totalSigners: 2,
    documentContent: $pdfBase64,
    documentFilename: 'contrato.pdf',
));

$session1 = $client->envelopes->addSession($envelope->envelopeId, new AddEnvelopeSessionRequest(
    signerName: 'João Silva',
    signerEmail: 'joao@example.com',
    policyProfile: 'CLICK_ONLY',
));

$session2 = $client->envelopes->addSession($envelope->envelopeId, new AddEnvelopeSessionRequest(
    signerName: 'Maria Santos',
    signerEmail: 'maria@example.com',
    policyProfile: 'CLICK_ONLY',
    signerIndex: 2,
));

echo $session1->url . ' ' . $session2->url;
```

## Configuração Avançada

### Guzzle Client customizado

Injete um `GuzzleHttp\Client` customizado (ex: para proxying, middleware ou métricas):

```php
use GuzzleHttp\Client as GuzzleClient;

$guzzle = new GuzzleClient(['proxy' => 'http://proxy:8080']);

$client = new SignDocsBrasilClient(new Config(
    clientId: 'seu_client_id',
    clientSecret: 'seu_client_secret',
    guzzle: $guzzle,
));
```

### Logging

O SDK aceita qualquer logger PSR-3 (`Psr\Log\LoggerInterface`). São logados apenas: método HTTP, path, status code e duração. Headers de autorização, corpos de request/response e tokens nunca são logados.

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('signdocs');
$logger->pushHandler(new StreamHandler('php://stdout'));

$client = new SignDocsBrasilClient(new Config(
    clientId: 'seu_client_id',
    clientSecret: 'seu_client_secret',
    logger: $logger,
));
```

### Timeout por requisição

Todas as operações aceitam `$timeout` (em segundos) como parâmetro opcional, que sobrescreve o timeout padrão do client:

```php
$tx = $client->transactions->get('tx_123', timeout: 5);
```

## Documentação

Para guias completos de integração com exemplos passo-a-passo de todos os fluxos de assinatura, veja a [documentação centralizada](../docs/README.md).
