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
                ->arrayNode('cors')
                    ->addDefaultsIfNotSet()
                    ->canBeEnabled()
                    ->children()
                        ->arrayNode('allow_origins')
                            ->scalarPrototype()->end()
                            ->beforeNormalization()
                                ->always(function ($value) {
                                    return (array) $value;
                                })
                            ->end()
                        ->end()
                        ->variableNode('allow_methods')
                            ->defaultTrue()
                            ->beforeNormalization()
                                ->always(function ($value) {
                                    return $value === true ? true : (array) $value;
                                })
                            ->end()
                        ->end()
                        ->variableNode('allow_headers')
                            ->defaultTrue()
                            ->beforeNormalization()
                                ->always(function ($value) {
                                    return $value === true ? true : (array) $value;
                                })
                            ->end()
                        ->end()
                        ->variableNode('expose_headers')
                            ->defaultTrue()
                            ->beforeNormalization()
                                ->always(function ($value) {
                                    return $value === true ? true : (array) $value;
                                })
                            ->end()
                        ->end()
                        ->booleanNode('allow_credentials')->defaultTrue()->end()
                        ->integerNode('maximum_age')->defaultNull()->end()
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
                ->arrayNode('api_doc_type_mapping')
                    ->variablePrototype()->end()
                    ->defaultValue([])
                ->end()
                ->booleanNode('api_doc_request_with_credentials')->defaultFalse()->end()
            ->end();

        return $treeBuilder;
    }
}
