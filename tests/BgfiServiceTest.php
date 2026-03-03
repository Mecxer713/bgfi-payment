<?php

namespace Mecxer713\BgfiPayment\Tests;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mecxer713\BgfiPayment\BgfiPaymentServiceProvider;
use Mecxer713\BgfiPayment\Services\BgfiService;
use Orchestra\Testbench\TestCase;

class BgfiServiceTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [BgfiPaymentServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cache.default', 'array');
    }

    public function test_it_throws_when_config_is_missing_required_keys(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new BgfiService([]);
    }

    public function test_collect_uses_token_and_returns_payload(): void
    {
        Cache::flush();

        $service = new BgfiService([
            'base_url'        => 'https://example.test',
            'consumer_id'     => 'id',
            'consumer_secret' => 'secret',
            'login'           => 'login',
            'password'        => 'pass',
            'currency'        => 'CDF',
            'token_ttl'       => 1,
        ]);

        Http::fake([
            'https://example.test/api/rakakash/oauth' => Http::response(['token' => 'abc'], 200),
            'https://example.test/api/v1/collect/process' => Http::response(['status' => 'ok'], 200),
        ]);

        $response = $service->collect([
            'amount'    => 1000,
            'phone'     => '243000000',
            'reference' => 'TEST-1',
        ]);

        $this->assertSame(['status' => 'ok'], $response);
    }

    public function test_it_throws_on_bgfi_error_code_even_with_http_200(): void
    {
        $service = new BgfiService([
            'base_url'        => 'https://example.test',
            'consumer_id'     => 'id',
            'consumer_secret' => 'secret',
            'login'           => 'login',
            'password'        => 'pass',
        ]);

        Http::fake([
            'https://example.test/api/rakakash/oauth' => Http::response(['code' => 4008, 'message' => 'Application Corrompue'], 200),
        ]);

        $this->expectException(\Mecxer713\BgfiPayment\Exceptions\BgfiApiException::class);
        $this->expectExceptionMessage('Application Corrompue');

        $service->getToken();
    }
}
