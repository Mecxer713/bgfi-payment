<?php

namespace Mecxer713\BgfiPayment\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('bgfi_payment');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('base_url')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('consumer_id')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('consumer_secret')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('login')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('password')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('currency')->defaultValue('CDF')->end()
                ->scalarNode('default_description')->defaultValue('Payment')->end()
                ->booleanNode('verify_ssl')->defaultTrue()->end()
                ->scalarNode('ca_path')->defaultNull()->end()
                ->integerNode('token_ttl')->defaultValue(3500)->end()
                ->scalarNode('user_agent')->defaultNull()->end()
                ->scalarNode('return_url')->defaultNull()->end()
            ->end();

        return $treeBuilder;
    }
}
