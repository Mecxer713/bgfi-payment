<?php

namespace Mecxer713\BgfiPayment\Symfony\DependencyInjection;

use Mecxer713\BgfiPayment\Services\BgfiService;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class BgfiPaymentExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('bgfi_payment.config', $config);

        $container->register('bgfi_payment.cache', Psr16Cache::class)
            ->setArguments([new Reference('cache.app')]);

        $container->register(BgfiService::class, BgfiService::class)
            ->setArguments([
                '%bgfi_payment.config%',
                null,
                new Reference('bgfi_payment.cache'),
            ])
            ->setPublic(true);

        $container->setAlias('bgfi_payment', BgfiService::class)->setPublic(true);
    }
}
