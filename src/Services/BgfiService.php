<?php

namespace Mecxer713\BgfiPayment\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Mecxer713\BgfiPayment\Exceptions\BgfiApiException;

class BgfiService
{
    protected array $config;
    protected string $userAgent;
    protected int $tokenTtl;
    protected string|bool $verify;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->userAgent = $config['user_agent'] ?? 'Mozilla/5.0 (Linux; Android 11; Pixel 5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.91 Mobile Safari/537.36';
        $this->tokenTtl = (int) ($config['token_ttl'] ?? 3500);
        $this->verify = $this->resolveVerifyOption($config);

        $this->guardConfig();
    }

    protected function guardConfig(): void
    {
        foreach (['base_url', 'consumer_id', 'consumer_secret', 'login', 'password'] as $key) {
            if (empty($this->config[$key])) {
                throw new InvalidArgumentException("Missing BGFI configuration value: {$key}");
            }
        }
    }

    protected function client(): PendingRequest
    {
        return Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => $this->userAgent,
        ])->withOptions(['verify' => $this->verify]);
    }

    protected function baseUrl(string $path = ''): string
    {
        return rtrim($this->config['base_url'], '/') . '/' . ltrim($path, '/');
    }

    protected function resolveVerifyOption(array $config): string|bool
    {
        $verify = $config['verify_ssl'] ?? true;

        if ($verify === false || $verify === 0 || $verify === 'false') {
            return false;
        }

        if (!empty($config['ca_path'])) {
            $path = $this->normalizePath($config['ca_path']);

            if (!file_exists($path)) {
                throw new InvalidArgumentException("CA bundle not found at {$path}");
            }

            return $path;
        }

        return true;
    }

    protected function normalizePath(string $path): string
    {
        $path = trim($path);

        if (function_exists('storage_path') && str_starts_with($path, 'storage')) {
            $relative = preg_replace('#^storage[\\\\/]?#', '', $path);

            return storage_path($relative);
        }

        return $path;
    }

    protected function throwIfFailed(Response $response, string $context): void
    {
        if ($response->failed()) {
            throw new BgfiApiException($context . " (status {$response->status()}): " . $response->body());
        }
    }

    public function getToken(): string
    {
        $cacheKey = 'bgfi_token_' . md5($this->config['consumer_id']);

        return Cache::remember($cacheKey, $this->tokenTtl, function () {
            $response = $this->client()
                ->withBasicAuth($this->config['login'], $this->config['password'])
                ->post($this->baseUrl('api/rakakash/oauth'), [
                    'consumerid'     => $this->config['consumer_id'],
                    'consumersecret' => $this->config['consumer_secret'],
                ]);

            if ($response->failed()) {
                throw BgfiApiException::authFailed($response->body(), $response->status());
            }

            $token = $response->json('token') ?? $response->json('access_token');

            if (!$token) {
                throw BgfiApiException::authFailed('Token not present in response', $response->status());
            }

            return $token;
        });
    }

    public function checkAccount(string $account, string $type = 'RAKAKASH', array $bankDetails = []): array
    {
        $payload = array_merge([
            'type'    => strtoupper($type),
            'account' => $account,
        ], $bankDetails);

        $response = $this->client()
            ->withToken($this->getToken())
            ->post($this->baseUrl('api/rakakash/checkaccount'), $payload);

        $this->throwIfFailed($response, 'Account verification failed');

        return $response->json();
    }

    public function deposit(string $phone, float $amount, ?string $currency = null): array
    {
        $response = $this->client()
            ->withToken($this->getToken())
            ->post($this->baseUrl('api/rakakash/deposit/rakakash'), [
                'compterakakash_destinataire' => $phone,
                'devise'                      => $currency ?? $this->config['currency'] ?? 'CDF',
                'montant'                     => $amount,
            ]);

        $this->throwIfFailed($response, 'Deposit request failed');

        return $response->json();
    }

    public function collect(array $data): array
    {
        $payload = [
            'amount'          => $data['amount'],
            'currency'        => $data['currency'] ?? $this->config['currency'] ?? 'CDF',
            'customer_msisdn' => $data['phone'],
            'external_ref'    => $data['reference'],
            'description'     => $data['description'] ?? $this->config['default_description'] ?? 'Payment',
            'return_url'      => $data['return_url'] ?? $this->config['return_url'] ?? null,
        ];

        $payload = array_filter($payload, fn ($value) => !is_null($value));

        $response = $this->client()
            ->withToken($this->getToken())
            ->withHeaders([
                'X-Consumer-ID' => $this->config['consumer_id'],
            ])
            ->post($this->baseUrl('api/v1/collect/process'), $payload);

        $this->throwIfFailed($response, 'Collect request failed');

        return $response->json();
    }

    public function getCollectStatus(string $externalRef): array
    {
        $response = $this->client()
            ->withToken($this->getToken())
            ->withHeaders([
                'X-Consumer-ID' => $this->config['consumer_id'],
            ])
            ->get($this->baseUrl('api/v1/collect/status/' . $externalRef));

        $this->throwIfFailed($response, 'Collect status request failed');

        return $response->json();
    }
}
