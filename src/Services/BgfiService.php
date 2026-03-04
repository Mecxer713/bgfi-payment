<?php

namespace Mecxer713\BgfiPayment\Services;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Mecxer713\BgfiPayment\Exceptions\BgfiApiException;
use Mecxer713\BgfiPayment\Support\Cache\ArrayCache;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

class BgfiService
{
    protected array $config;
    protected string $userAgent;
    protected int $tokenTtl;
    protected string|bool $verify;
    protected ClientInterface $http;
    protected CacheInterface $cache;

    public function __construct(array $config, ?ClientInterface $httpClient = null, ?CacheInterface $cache = null)
    {
        $this->config = $config;
        $this->userAgent = $config['user_agent'] ?? 'Mozilla/5.0 (Linux; Android 11; Pixel 5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/90.0.4430.91 Mobile Safari/537.36';
        $this->tokenTtl = (int) ($config['token_ttl'] ?? 3500);
        $this->verify = $this->resolveVerifyOption($config);

        $this->guardConfig();

        $this->http = $httpClient ?? $this->buildHttpClient();
        $this->cache = $cache ?? new ArrayCache();
    }

    protected function guardConfig(): void
    {
        foreach (['base_url', 'consumer_id', 'consumer_secret', 'login', 'password'] as $key) {
            if (empty($this->config[$key])) {
                throw new InvalidArgumentException("Missing BGFI configuration value: {$key}");
            }
        }
    }

    protected function buildHttpClient(): ClientInterface
    {
        return new Client([
            'headers' => [
                'Accept'           => 'application/json',
                'Content-Type'     => 'application/json',
                // Empreinte mobile utilisee par le client officiel
                'User-Agent'       => $this->userAgent ?: 'Dalvik/2.1.0 (Linux; U; Android 11; Pixel 5 Build/RQ3A.210605.005)',
                'X-Requested-With' => 'com.omnitech.rakakash',
                'Connection'       => 'Keep-Alive',
            ],
            'verify'  => $this->verify,
            'timeout' => 60,
        ]);
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

    protected function send(string $method, string $path, array $options = []): array
    {
        try {
            $response = $this->http->request($method, $this->baseUrl($path), $options);
        } catch (GuzzleException $e) {
            throw new BgfiApiException("HTTP error during {$method} {$path}: " . $e->getMessage(), $e->getCode());
        }

        $data = $this->decode($response);
        $this->assertSuccess($response, $data, "{$method} {$path}");

        return $data;
    }

    protected function decode(ResponseInterface $response): array
    {
        $json = (string) $response->getBody();
        $data = json_decode($json, true);

        return is_array($data) ? $data : [];
    }

    protected function assertSuccess(ResponseInterface $response, array $data, string $context): void
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($status >= 400) {
            throw new BgfiApiException($context . " (status {$status}): " . $body);
        }

        $code = $data['code'] ?? null;
        $message = $data['message'] ?? null;

        // BGFI sometimes returns HTTP 200 with error codes (e.g., 4008)
        if ($code !== null && (int) $code !== 2000) {
            throw new BgfiApiException($context . " (code {$code}): " . ($message ?? $body));
        }
    }

    protected function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->getToken(),
        ];
    }

    public function getToken(): string
    {
        $cacheKey = 'bgfi_token_' . md5($this->config['consumer_id']);
        $cached = $this->cache->get($cacheKey);

        if ($cached) {
            return $cached;
        }

        try {
            $response = $this->http->request('POST', $this->baseUrl('api/rakakash/oauth'), [
                'auth' => [$this->config['login'], $this->config['password']],
                'json' => [
                    'consumerid'     => $this->config['consumer_id'],
                    'consumersecret' => $this->config['consumer_secret'],
                ],
            ]);
        } catch (GuzzleException $e) {
            throw BgfiApiException::authFailed($e->getMessage(), $e->getCode());
        }

        $data = $this->decode($response);
        $this->assertSuccess($response, $data, 'Authentication failed');

        $token = $data['token'] ?? $data['access_token'] ?? null;

        if (!$token) {
            throw BgfiApiException::authFailed('Token not present in response', $response->getStatusCode());
        }

        $this->cache->set($cacheKey, $token, $this->tokenTtl);

        return $token;
    }

    public function checkAccount(string $account, string $type = 'RAKAKASH', array $bankDetails = []): array
    {
        $payload = array_merge([
            'type'    => strtoupper($type),
            'account' => $account,
        ], $bankDetails);

        $responseData = $this->send('POST', 'api/rakakash/checkaccount', [
            'headers' => $this->authHeaders(),
            'json'    => $payload,
        ]);

        if (isset($responseData['code']) && $responseData['code'] == 4008) {
            throw new \Exception("Securite Omnitech (4008) : Empreinte de requete refusee.");
        }

        return $responseData;
    }

    public function deposit(string $phone, float $amount, ?string $currency = null): array
    {
        return $this->send('POST', 'api/rakakash/deposit/rakakash', [
            'headers' => $this->authHeaders(),
            'json'    => [
                'compterakakash_destinataire' => $phone,
                'devise'                      => $currency ?? $this->config['currency'] ?? 'CDF',
                'montant'                     => $amount,
            ],
        ]);
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

        $payload = array_filter($payload, fn($value) => !is_null($value));

        return $this->send('POST', 'api/v1/collect/process', [
            'headers' => array_merge($this->authHeaders(), [
                'X-Consumer-ID' => $this->config['consumer_id'],
            ]),
            'json' => $payload,
        ]);
    }

    public function getCollectStatus(string $externalRef): array
    {
        return $this->send('GET', 'api/v1/collect/status/' . $externalRef, [
            'headers' => array_merge($this->authHeaders(), [
                'X-Consumer-ID' => $this->config['consumer_id'],
            ]),
        ]);
    }
}
