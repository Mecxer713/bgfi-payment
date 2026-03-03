<?php

namespace Mecxer713\BgfiPayment;

use Illuminate\Support\ServiceProvider;
use Mecxer713\BgfiPayment\Services\BgfiService;
use Mecxer713\BgfiPayment\Console\TestPaymentCommand;

class BgfiPaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/bgfi.php', 'bgfi');

        $this->app->singleton(BgfiService::class, function ($app) {
            return new BgfiService($app['config']->get('bgfi'));
        });

        // Facade alias
        $this->app->alias(BgfiService::class, 'bgfi-payment');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([TestPaymentCommand::class]);

            $this->publishes([
                __DIR__.'/../config/bgfi.php' => config_path('bgfi.php'),
            ], 'bgfi-config');
        }

        if (config('bgfi.register_callback_route', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/bgfi.php');
        }
    }
}
