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
                ->booleanNode('format_listener')->defaultFalse()->end()
                ->booleanNode('request_body_listener')->defaultFalse()->end()
                ->booleanNode('access_denied_listener')->defaultFalse()->end()
                ->arrayNode('formats')
                    ->scalarPrototype()->end()
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
                ->arrayNode('limit_default_options')
                    ->children()
                        ->integerNode('default_limit')->end()
                    ->end()
                    ->addDefaultsIfNotSet()
                ->end()
                ->arrayNode('serializer_doctrine_type_mapping')
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('nelmio_api_doc_type_mapping')
                    ->variablePrototype()->end()
                    ->defaultValue([])
                ->end()
            ->end();

        return $treeBuilder;
    }
}
