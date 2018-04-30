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
                ->booleanNode('route_not_found_listener')->defaultTrue()->end()
                ->booleanNode('access_denied_listener')->defaultTrue()->end()
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
            ->end();

        return $treeBuilder;
    }
}
