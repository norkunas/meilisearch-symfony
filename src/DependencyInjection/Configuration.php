<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\DependencyInjection;

use Meilisearch\Bundle\Searchable;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('meili_search');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('url')->end()
                ->scalarNode('api_key')->end()
                ->scalarNode('prefix')
                    ->defaultValue(null)
                ->end()
                ->scalarNode('nbResults')
                    ->defaultValue(20)
                ->end()
                ->scalarNode('batchSize')
                    ->defaultValue(500)
                ->end()
                ->arrayNode('doctrineSubscribedEvents')
                    ->prototype('scalar')->end()
                    ->defaultValue(['postPersist', 'postUpdate', 'preRemove'])
                ->end()
                ->scalarNode('serializer')
                    ->defaultValue('serializer')
                ->end()
                ->arrayNode('indices')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('class')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->booleanNode('enable_serializer_groups')
                                ->info('When set to true, it will call normalize method with an extra groups parameter "groups" => [Searchable::NORMALIZATION_GROUP]')
                                ->defaultFalse()
                            ->end()
                            ->arrayNode('serializer_groups')
                                ->info('When setting a different value, normalization will be called with it instead of "Searchable::NORMALIZATION_GROUP".')
                                ->defaultValue([Searchable::NORMALIZATION_GROUP])
                                ->scalarPrototype()->end()
                            ->end()
                            ->scalarNode('index_if')
                                ->info('Property accessor path (like method or property name) used to decide if an entry should be indexed.')
                                ->defaultNull()
                            ->end()
                            ->arrayNode('settings')
                                ->info('Configure indices settings, see: https://docs.meilisearch.com/guides/advanced_guides/settings.html')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->arrayNode('displayedAttributes')
                                        ->canBeEnabled()
                                        ->beforeNormalization()
                                            ->ifArray()
                                            ->then(static function ($v) {
                                                if (\is_array($v) && true === $v['enabled']) {
                                                    $service = $v['_service'] ?? null;
                                                    unset($v['enabled'], $v['_service']);

                                                    return ['enabled' => true, 'value' => $v, '_service' => $service];
                                                }

                                                return $v;
                                            })
                                        ->end()
                                        ->children()
                                            ->arrayNode('value')
                                                ->scalarPrototype()->end()
                                            ->end()
                                            ->scalarNode('_service')->defaultNull()->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('distinctAttribute')
                                        ->canBeEnabled()
                                        ->beforeNormalization()
                                            ->ifString()
                                            ->then(function ($v) {
                                                return ['enabled' => true, 'value' => $v];
                                            })
                                        ->end()
                                        ->children()
                                            ->scalarNode('value')->defaultNull()->cannotBeEmpty()->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('faceting')
                                        ->canBeEnabled()
                                        ->children()
                                            ->integerNode('maxValuesPerFacet')->defaultNull()->end()
                                            ->arrayNode('sortFacetValuesBy')
                                                ->normalizeKeys(false)
                                                ->variablePrototype()->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('filterableAttributes')
                                        ->canBeEnabled()
                                        ->beforeNormalization()
                                            ->ifArray()
                                            ->then(static function ($v) {
                                                if (\is_array($v) && true === $v['enabled']) {
                                                    $service = $v['_service'] ?? null;
                                                    unset($v['enabled'], $v['_service']);

                                                    return ['enabled' => true, 'value' => $v, '_service' => $service];
                                                }

                                                return $v;
                                            })
                                        ->end()
                                        ->children()
                                            ->arrayNode('value')
                                                ->scalarPrototype()->end()
                                            ->end()
                                            ->scalarNode('_service')->defaultNull()->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('pagination')
                                        ->canBeEnabled()
                                        ->children()
                                            ->integerNode('maxTotalHits')->defaultNull()->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('rankingRules')
                                        ->canBeEnabled()
                                        ->beforeNormalization()
                                            ->ifArray()
                                            ->then(static function ($v) {
                                                if (\is_array($v) && true === $v['enabled']) {
                                                    $service = $v['_service'] ?? null;
                                                    unset($v['enabled'], $v['_service']);

                                                    return ['enabled' => true, 'value' => $v, '_service' => $service];
                                                }

                                                return $v;
                                            })
                                        ->end()
                                        ->children()
                                            ->arrayNode('value')
                                                ->scalarPrototype()->end()
                                            ->end()
                                            ->scalarNode('_service')->defaultNull()->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('searchableAttributes')
                                        ->canBeEnabled()
                                        ->beforeNormalization()
                                            ->ifArray()
                                            ->then(static function ($v) {
                                                if (\is_array($v) && true === $v['enabled']) {
                                                    $service = $v['_service'] ?? null;
                                                    unset($v['enabled'], $v['_service']);

                                                    return ['enabled' => true, 'value' => $v, '_service' => $service];
                                                }

                                                return $v;
                                            })
                                        ->end()
                                        ->children()
                                            ->arrayNode('value')
                                                ->scalarPrototype()->end()
                                            ->end()
                                            ->scalarNode('_service')->defaultNull()->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('sortableAttributes')
                                        ->canBeEnabled()
                                        ->beforeNormalization()
                                            ->ifArray()
                                            ->then(static function ($v) {
                                                if (\is_array($v) && true === $v['enabled']) {
                                                    $service = $v['_service'] ?? null;
                                                    unset($v['enabled'], $v['_service']);

                                                    return ['enabled' => true, 'value' => $v, '_service' => $service];
                                                }

                                                return $v;
                                            })
                                        ->end()
                                        ->children()
                                            ->arrayNode('value')
                                                ->scalarPrototype()->end()
                                            ->end()
                                            ->scalarNode('_service')->defaultNull()->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('stopWords')
                                        ->canBeEnabled()
                                        ->beforeNormalization()
                                            ->ifArray()
                                            ->then(static function ($v) {
                                                if (\is_array($v) && true === $v['enabled']) {
                                                    $service = $v['_service'] ?? null;
                                                    unset($v['enabled'], $v['_service']);

                                                    return ['enabled' => true, 'value' => $v, '_service' => $service];
                                                }

                                                return $v;
                                            })
                                        ->end()
                                        ->children()
                                            ->arrayNode('value')
                                                ->scalarPrototype()->end()
                                            ->end()
                                            ->scalarNode('_service')->defaultNull()->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('synonyms')
                                        ->canBeEnabled()
                                        ->beforeNormalization()
                                            ->always()
                                            ->then(static function ($v) {
                                                if (\is_array($v) && true === $v['enabled']) {
                                                    $service = $v['_service'] ?? null;
                                                    unset($v['enabled'], $v['_service']);

                                                    return ['enabled' => true, 'value' => $v, '_service' => $service];
                                                }

                                                return $v;
                                            })
                                        ->end()
                                        ->children()
                                            ->arrayNode('value')
                                            ->variablePrototype()
                                                ->beforeNormalization()->castToArray()->end()
                                            ->end()
                                            ->end()
                                            ->scalarNode('_service')->defaultNull()->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('typoTolerance')
                                        ->canBeDisabled()
                                        ->children()
                                            ->arrayNode('minWordSizeForTypos')
                                                ->addDefaultsIfNotSet()
                                                ->children()
                                                    ->integerNode('oneTypo')->defaultNull()->end()
                                                    ->integerNode('twoTypos')->defaultNull()->end()
                                                ->end()
                                            ->end()
                                            ->arrayNode('disableOnWords')
                                                ->scalarPrototype()->end()
                                            ->end()
                                            ->arrayNode('disableOnAttributes')
                                                ->scalarPrototype()->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
