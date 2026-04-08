<?php

declare(strict_types=1);

namespace SignDocsBrasil\Api;

use GuzzleHttp\Client as GuzzleClient;
use Psr\Log\LoggerInterface;

final class Config
{
    public const DEFAULT_BASE_URL = 'https://api.signdocs.com.br';
    public const DEFAULT_TIMEOUT = 30;
    public const DEFAULT_MAX_RETRIES = 5;
    public const DEFAULT_SCOPES = [
        'transactions:read',
        'transactions:write',
        'steps:write',
        'evidence:read',
        'webhooks:write',
    ];

    public readonly string $baseUrl;
    public readonly int $timeout;
    public readonly int $maxRetries;
    /** @var string[] */
    public readonly array $scopes;

    /**
     * @param string               $clientId      OAuth2 client ID
     * @param string|null          $clientSecret  OAuth2 client secret (for client_secret auth)
     * @param string|null          $privateKey    PEM-encoded ES256 private key (for private_key_jwt auth)
     * @param string|null          $kid           Key ID for private_key_jwt auth
     * @param string|null          $baseUrl       API base URL (defaults to production)
     * @param int|null             $timeout       HTTP timeout in seconds (defaults to 30)
     * @param int|null             $maxRetries    Maximum retry attempts (defaults to 5)
     * @param string[]|null        $scopes        OAuth2 scopes
     * @param LoggerInterface|null $logger        PSR-3 logger for request/response logging
     * @param GuzzleClient|null    $guzzle        Custom Guzzle client instance
     */
    public function __construct(
        public readonly string $clientId,
        public readonly ?string $clientSecret = null,
        public readonly ?string $privateKey = null,
        public readonly ?string $kid = null,
        ?string $baseUrl = null,
        ?int $timeout = null,
        ?int $maxRetries = null,
        ?array $scopes = null,
        public readonly ?LoggerInterface $logger = null,
        public readonly ?GuzzleClient $guzzle = null,
    ) {
        if ($clientId === '') {
            throw new \InvalidArgumentException('clientId is required');
        }

        if ($clientSecret === null && $privateKey === null) {
            throw new \InvalidArgumentException(
                'Either clientSecret or privateKey+kid is required'
            );
        }

        if ($privateKey !== null && ($kid === null || $kid === '')) {
            throw new \InvalidArgumentException(
                'kid is required when using privateKey'
            );
        }

        $this->baseUrl = rtrim($baseUrl ?? self::DEFAULT_BASE_URL, '/');
        $this->timeout = $timeout ?? self::DEFAULT_TIMEOUT;
        $this->maxRetries = $maxRetries ?? self::DEFAULT_MAX_RETRIES;
        $this->scopes = $scopes ?? self::DEFAULT_SCOPES;
    }
}
