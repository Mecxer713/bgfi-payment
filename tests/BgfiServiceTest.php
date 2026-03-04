<?php

namespace Mecxer713\BgfiPayment\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mecxer713\BgfiPayment\Exceptions\BgfiApiException;
use Mecxer713\BgfiPayment\Services\BgfiService;
use Mecxer713\BgfiPayment\Support\Cache\ArrayCache;
use PHPUnit\Framework\TestCase;

class BgfiServiceTest extends TestCase
{
    private array $baseConfig = [
        'base_url'        => 'https://example.test',
        'consumer_id'     => 'id',
        'consumer_secret' => 'secret',
        'login'           => 'login',
        'password'        => 'pass',
        'currency'        => 'CDF',
        'token_ttl'       => 1,
    ];

    public function test_it_throws_when_config_is_missing_required_keys(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new BgfiService([]);
    }

    public function test_collect_uses_token_and_returns_payload(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['token' => 'abc'])),
            new Response(200, [], json_encode(['status' => 'ok'])),
        ]);

        $service = new BgfiService(
            $this->baseConfig,
            new Client(['handler' => HandlerStack::create($mock)]),
            new ArrayCache()
        );

        $response = $service->collect([
            'amount'    => 1000,
            'phone'     => '243000000',
            'reference' => 'TEST-1',
        ]);

        $this->assertSame(['status' => 'ok'], $response);
    }

    public function test_it_throws_on_bgfi_error_code_even_with_http_200(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['code' => 4008, 'message' => 'Application Corrompue'])),
        ]);

        $service = new BgfiService(
            $this->baseConfig,
            new Client(['handler' => HandlerStack::create($mock)]),
            new ArrayCache()
        );

        $this->expectException(BgfiApiException::class);
        $this->expectExceptionMessage('Application Corrompue');

        $service->getToken();
    }
}
