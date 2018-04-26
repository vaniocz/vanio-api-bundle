<?php
namespace Vanio\ApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder;
        $treeBuilder->root('vanio_api')
            ->children()
                ->arrayNode('access_denied_listener')
                    ->canBeDisabled()
                    ->children()
                        ->arrayNode('formats')
                            ->prototype('scalar')->end()
                            ->defaultValue(['json'])
                            ->beforeNormalization()
                                ->ifTrue(function ($value) {
                                    return !is_array($value);
                                })
                                ->then(function ($value) {
                                    return [$value];
                                })
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
