<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\Tests\Unit;

use Meilisearch\Bundle\DependencyInjection\Configuration;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Class ConfigurationTest.
 */
class ConfigurationTest extends KernelTestCase
{
    /**
     * @dataProvider dataTestConfigurationTree
     */
    public function testConfigurationTree($inputConfig, $expectedConfig): void
    {
        $configuration = new Configuration();

        $node = $configuration->getConfigTreeBuilder()->buildTree();
        $normalizedConfig = $node->normalize($inputConfig);
        $finalizedConfig = $node->finalize($normalizedConfig);

        $this->assertEquals($expectedConfig, $finalizedConfig);
    }

    public function dataTestConfigurationTree(): array
    {
        return [
            'test empty config for default value' => [
                [],
                [
                    'prefix' => null,
                    'nbResults' => 20,
                    'batchSize' => 500,
                    'serializer' => 'serializer',
                    'doctrineSubscribedEvents' => ['postPersist', 'postUpdate', 'preRemove'],
                    'indices' => [],
                ],
            ],
            'Simple config' => [
                [
                    'prefix' => 'sf_',
                    'nbResults' => 40,
                    'batchSize' => 100,
                ],
                [
                    'prefix' => 'sf_',
                    'nbResults' => 40,
                    'batchSize' => 100,
                    'serializer' => 'serializer',
                    'doctrineSubscribedEvents' => ['postPersist', 'postUpdate', 'preRemove'],
                    'indices' => [],
                ],
            ],
            'Index config' => [
                [
                    'prefix' => 'sf_',
                    'indices' => [
                        ['name' => 'posts', 'class' => 'App\Entity\Post', 'index_if' => null],
                        [
                            'name' => 'tags',
                            'class' => 'App\Entity\Tag',
                            'enable_serializer_groups' => true,
                            'index_if' => null,
                        ],
                    ],
                ],
                [
                    'prefix' => 'sf_',
                    'nbResults' => 20,
                    'batchSize' => 500,
                    'serializer' => 'serializer',
                    'doctrineSubscribedEvents' => ['postPersist', 'postUpdate', 'preRemove'],
                    'indices' => [
                        0 => [
                            'name' => 'posts',
                            'class' => 'App\Entity\Post',
                            'enable_serializer_groups' => false,
                            'serializer_groups' => ['searchable'],
                            'index_if' => null,
                            'settings' => [
                                'displayedAttributes' => ['enabled' => false, 'value' => [], '_service' => null],
                                'distinctAttribute' => ['enabled' => false, 'value' => null],
                                'faceting' => ['enabled' => false, 'maxValuesPerFacet' => null, 'sortFacetValuesBy' => []],
                                'filterableAttributes' => ['enabled' => false, 'value' => [], '_service' => null],
                                'pagination' => ['enabled' => false, 'maxTotalHits' => null],
                                'rankingRules' => ['enabled' => false, 'value' => [], '_service' => null],
                                'searchableAttributes' => ['enabled' => false, 'value' => [], '_service' => null],
                                'sortableAttributes' => ['enabled' => false, 'value' => [], '_service' => null],
                                'stopWords' => ['enabled' => false, 'value' => [], '_service' => null],
                                'synonyms' => ['enabled' => false, 'value' => [], '_service' => null],
                                'typoTolerance' => [
                                    'enabled' => true,
                                    'minWordSizeForTypos' => ['oneTypo' => null, 'twoTypos' => null],
                                    'disableOnWords' => [],
                                    'disableOnAttributes' => [],
                                ],
                            ],
                        ],
                        1 => [
                            'name' => 'tags',
                            'class' => 'App\Entity\Tag',
                            'enable_serializer_groups' => true,
                            'serializer_groups' => ['searchable'],
                            'index_if' => null,
                            'settings' => [
                                'displayedAttributes' => ['enabled' => false, 'value' => [], '_service' => null],
                                'distinctAttribute' => ['enabled' => false, 'value' => null],
                                'faceting' => ['enabled' => false, 'maxValuesPerFacet' => null, 'sortFacetValuesBy' => []],
                                'filterableAttributes' => ['enabled' => false, 'value' => [], '_service' => null],
                                'pagination' => ['enabled' => false, 'maxTotalHits' => null],
                                'rankingRules' => ['enabled' => false, 'value' => [], '_service' => null],
                                'searchableAttributes' => ['enabled' => false, 'value' => [], '_service' => null],
                                'sortableAttributes' => ['enabled' => false, 'value' => [], '_service' => null],
                                'stopWords' => ['enabled' => false, 'value' => [], '_service' => null],
                                'synonyms' => ['enabled' => false, 'value' => [], '_service' => null],
                                'typoTolerance' => [
                                    'enabled' => true,
                                    'minWordSizeForTypos' => ['oneTypo' => null, 'twoTypos' => null],
                                    'disableOnWords' => [],
                                    'disableOnAttributes' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'same index for multiple models' => [
                [
                    'prefix' => 'sf_',
                    'indices' => [
                        [
                            'name' => 'items',
                            'class' => 'App\Entity\Post',
                            'enable_serializer_groups' => false,
                            'index_if' => null,
                            'settings' => [],
                        ],
                        [
                            'name' => 'items',
                            'class' => 'App\Entity\Tag',
                            'enable_serializer_groups' => false,
                            'index_if' => null,
                            'settings' => [],
                        ],
                    ],
                    'nbResults' => 20,
                    'batchSize' => 500,
                    'serializer' => 'serializer',
                    'doctrineSubscribedEvents' => ['postPersist', 'postUpdate', 'preRemove'],
                ],
                [
                    'prefix' => 'sf_',
                    'indices' => [
                        [
                            'name' => 'items',
                            'class' => 'App\Entity\Post',
                            'enable_serializer_groups' => false,
                            'serializer_groups' => ['searchable'],
                            'index_if' => null,
                            'settings' => [
                                'displayedAttributes' => ['enabled' => false, 'value' => [], '_service' => null],
                                'distinctAttribute' => ['enabled' => false, 'value' => null],
                                'faceting' => ['enabled' => false, 'maxValuesPerFacet' => null, 'sortFacetValuesBy' => []],
                                'filterableAttributes' => ['enabled' => false, 'value' => [], '_service' => null],
                                'pagination' => ['enabled' => false, 'maxTotalHits' => null],
                                'rankingRules' => ['enabled' => false, 'value' => [], '_service' => null],
                                'searchableAttributes' => ['enabled' => false, 'value' => [], '_service' => null],
                                'sortableAttributes' => ['enabled' => false, 'value' => [], '_service' => null],
                                'stopWords' => ['enabled' => false, 'value' => [], '_service' => null],
                                'synonyms' => ['enabled' => false, 'value' => [], '_service' => null],
                                'typoTolerance' => [
                                    'enabled' => true,
                                    'minWordSizeForTypos' => ['oneTypo' => null, 'twoTypos' => null],
                                    'disableOnWords' => [],
                                    'disableOnAttributes' => [],
                                ],
                            ],
                        ],
                        [
                            'name' => 'items',
                            'class' => 'App\Entity\Tag',
                            'enable_serializer_groups' => false,
                            'serializer_groups' => ['searchable'],
                            'index_if' => null,
                            'settings' => [
                                'displayedAttributes' => ['enabled' => false, 'value' => [], '_service' => null],
                                'distinctAttribute' => ['enabled' => false, 'value' => null],
                                'faceting' => ['enabled' => false, 'maxValuesPerFacet' => null, 'sortFacetValuesBy' => []],
                                'filterableAttributes' => ['enabled' => false, 'value' => [], '_service' => null],
                                'pagination' => ['enabled' => false, 'maxTotalHits' => null],
                                'rankingRules' => ['enabled' => false, 'value' => [], '_service' => null],
                                'searchableAttributes' => ['enabled' => false, 'value' => [], '_service' => null],
                                'sortableAttributes' => ['enabled' => false, 'value' => [], '_service' => null],
                                'stopWords' => ['enabled' => false, 'value' => [], '_service' => null],
                                'synonyms' => ['enabled' => false, 'value' => [], '_service' => null],
                                'typoTolerance' => [
                                    'enabled' => true,
                                    'minWordSizeForTypos' => ['oneTypo' => null, 'twoTypos' => null],
                                    'disableOnWords' => [],
                                    'disableOnAttributes' => [],
                                ],
                            ],
                        ],
                    ],
                    'nbResults' => 20,
                    'batchSize' => 500,
                    'serializer' => 'serializer',
                    'doctrineSubscribedEvents' => ['postPersist', 'postUpdate', 'preRemove'],
                ],
            ],
            'Custom serializer groups' => [
                [
                    'prefix' => 'sf_',
                    'indices' => [
                        [
                            'name' => 'items',
                            'class' => 'App\Entity\Post',
                            'enable_serializer_groups' => true,
                            'serializer_groups' => ['post.public', 'post.private'],
                            'index_if' => null,
                            'settings' => [],
                        ],
                    ],
                ],
                [
                    'prefix' => 'sf_',
                    'indices' => [
                        [
                            'name' => 'items',
                            'class' => 'App\Entity\Post',
                            'enable_serializer_groups' => true,
                            'serializer_groups' => ['post.public', 'post.private'],
                            'index_if' => null,
                            'settings' => [
                                'displayedAttributes' => ['enabled' => false, 'value' => [], '_service' => null],
                                'distinctAttribute' => ['enabled' => false, 'value' => null],
                                'faceting' => ['enabled' => false, 'maxValuesPerFacet' => null, 'sortFacetValuesBy' => []],
                                'filterableAttributes' => ['enabled' => false, 'value' => [], '_service' => null],
                                'pagination' => ['enabled' => false, 'maxTotalHits' => null],
                                'rankingRules' => ['enabled' => false, 'value' => [], '_service' => null],
                                'searchableAttributes' => ['enabled' => false, 'value' => [], '_service' => null],
                                'sortableAttributes' => ['enabled' => false, 'value' => [], '_service' => null],
                                'stopWords' => ['enabled' => false, 'value' => [], '_service' => null],
                                'synonyms' => ['enabled' => false, 'value' => [], '_service' => null],
                                'typoTolerance' => [
                                    'enabled' => true,
                                    'minWordSizeForTypos' => ['oneTypo' => null, 'twoTypos' => null],
                                    'disableOnWords' => [],
                                    'disableOnAttributes' => [],
                                ],
                            ],
                        ],
                    ],
                    'nbResults' => 20,
                    'batchSize' => 500,
                    'serializer' => 'serializer',
                    'doctrineSubscribedEvents' => ['postPersist', 'postUpdate', 'preRemove'],
                ],
            ],
            'Custom settings' => [
                [
                    'prefix' => 'sf_',
                    'indices' => [
                        [
                            'name' => 'items',
                            'class' => 'App\Entity\Post',
                            'settings' => [
                                'displayedAttributes' => ['title', 'overview', 'genres', 'release_date.year'],
                                'distinctAttribute' => 'skuid',
                                'faceting' => ['maxValuesPerFacet' => 100, 'sortFacetValuesBy' => ['*' => 'alpha']],
                                'filterableAttributes' => ['genres', 'director', 'release_date.year'],
                                'pagination' => ['maxTotalHits' => 1000],
                                'rankingRules' => ['words', 'typo', 'proximity', 'attribute', 'sort', 'exactness', 'release_date:desc'],
                                'searchableAttributes' => ['title', 'overview', 'genres', 'release_date.year'],
                                'sortableAttributes' => ['price', 'author.surname'],
                                'stopWords' => ['of', 'the', 'to'],
                                'synonyms' => ['Résumé' => ['CV'], 'CV' => ['Résumé'], 'world of warcraft' => 'wow', 'wolverine' => ['xmen', 'logan']],
                                'typoTolerance' => [
                                    'enabled' => true,
                                    'minWordSizeForTypos' => ['oneTypo' => 5, 'twoTypos' => 9],
                                    'disableOnWords' => ['hey'],
                                    'disableOnAttributes' => ['release_date'],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'prefix' => 'sf_',
                    'indices' => [
                        [
                            'name' => 'items',
                            'class' => 'App\Entity\Post',
                            'enable_serializer_groups' => false,
                            'serializer_groups' => ['searchable'],
                            'index_if' => null,
                            'settings' => [
                                'displayedAttributes' => ['enabled' => true, 'value' => ['title', 'overview', 'genres', 'release_date.year'], '_service' => null],
                                'distinctAttribute' => ['enabled' => true, 'value' => 'skuid'],
                                'faceting' => ['enabled' => true, 'maxValuesPerFacet' => 100, 'sortFacetValuesBy' => ['*' => 'alpha']],
                                'filterableAttributes' => ['enabled' => true, 'value' => ['genres', 'director', 'release_date.year'], '_service' => null],
                                'pagination' => ['enabled' => true, 'maxTotalHits' => 1000],
                                'rankingRules' => ['enabled' => true, 'value' => ['words', 'typo', 'proximity', 'attribute', 'sort', 'exactness', 'release_date:desc'], '_service' => null],
                                'searchableAttributes' => ['enabled' => true, 'value' => ['title', 'overview', 'genres', 'release_date.year'], '_service' => null],
                                'sortableAttributes' => ['enabled' => true, 'value' => ['price', 'author.surname'], '_service' => null],
                                'stopWords' => ['enabled' => true, 'value' => ['of', 'the', 'to'], '_service' => null],
                                'synonyms' => ['enabled' => true, 'value' => ['Résumé' => ['CV'], 'CV' => ['Résumé'], 'world of warcraft' => ['wow'], 'wolverine' => ['xmen', 'logan']], '_service' => null],
                                'typoTolerance' => [
                                    'enabled' => true,
                                    'minWordSizeForTypos' => ['oneTypo' => 5, 'twoTypos' => 9],
                                    'disableOnWords' => ['hey'],
                                    'disableOnAttributes' => ['release_date'],
                                ],
                            ],
                        ],
                    ],
                    'nbResults' => 20,
                    'batchSize' => 500,
                    'serializer' => 'serializer',
                    'doctrineSubscribedEvents' => ['postPersist', 'postUpdate', 'preRemove'],
                ],
            ],
            'Using services for settings' => [
                [
                    'prefix' => 'sf_',
                    'indices' => [
                        [
                            'name' => 'items',
                            'class' => 'App\Entity\Post',
                            'settings' => [
                                'displayedAttributes' => ['_service' => '@App\Meilisearch\DisplayedAttributes'],
                                'filterableAttributes' => ['_service' => '@App\Meilisearch\FilterableAttributes'],
                                'rankingRules' => ['_service' => '@App\Meilisearch\RankingRules'],
                                'searchableAttributes' => ['_service' => '@App\Meilisearch\SearchableAttributes'],
                                'sortableAttributes' => ['_service' => '@App\Meilisearch\SortableAttributes'],
                                'stopWords' => ['_service' => '@App\Meilisearch\StopWords'],
                                'synonyms' => ['_service' => '@App\Meilisearch\Synonyms'],
                            ],
                        ],
                    ],
                ],
                [
                    'prefix' => 'sf_',
                    'indices' => [
                        [
                            'name' => 'items',
                            'class' => 'App\Entity\Post',
                            'enable_serializer_groups' => false,
                            'serializer_groups' => ['searchable'],
                            'index_if' => null,
                            'settings' => [
                                'displayedAttributes' => ['enabled' => true, 'value' => [], '_service' => '@App\Meilisearch\DisplayedAttributes'],
                                'distinctAttribute' => ['enabled' => false, 'value' => null],
                                'faceting' => ['enabled' => false, 'maxValuesPerFacet' => null, 'sortFacetValuesBy' => []],
                                'filterableAttributes' => ['enabled' => true, 'value' => [], '_service' => '@App\Meilisearch\FilterableAttributes'],
                                'pagination' => ['enabled' => false, 'maxTotalHits' => null],
                                'rankingRules' => ['enabled' => true, 'value' => [], '_service' => '@App\Meilisearch\RankingRules'],
                                'searchableAttributes' => ['enabled' => true, 'value' => [], '_service' => '@App\Meilisearch\SearchableAttributes'],
                                'sortableAttributes' => ['enabled' => true, 'value' => [], '_service' => '@App\Meilisearch\SortableAttributes'],
                                'stopWords' => ['enabled' => true, 'value' => [], '_service' => '@App\Meilisearch\StopWords'],
                                'synonyms' => ['enabled' => true, 'value' => [], '_service' => '@App\Meilisearch\Synonyms'],
                                'typoTolerance' => [
                                    'enabled' => true,
                                    'minWordSizeForTypos' => ['oneTypo' => null, 'twoTypos' => null],
                                    'disableOnWords' => [],
                                    'disableOnAttributes' => [],
                                ],
                            ],
                        ],
                    ],
                    'nbResults' => 20,
                    'batchSize' => 500,
                    'serializer' => 'serializer',
                    'doctrineSubscribedEvents' => ['postPersist', 'postUpdate', 'preRemove'],
                ],
            ],
//            'Settings subset' => [
//                [
//                    'prefix' => 'sf_',
//                    'indices' => [
//                        [
//                            'name' => 'items',
//                            'class' => 'App\Entity\Post',
//                            'settings' => [
//                                'displayedAttributes' => ['title', 'overview', 'genres', 'release_date.year'],
//                                'distinctAttribute' => 'skuid',
//                                'faceting' => ['maxValuesPerFacet' => 100],
//                                'filterableAttributes' => ['genres', 'director', 'release_date.year'],
//                                'pagination' => ['maxTotalHits' => 1000],
//                                'rankingRules' => ['words', 'typo', 'proximity', 'attribute', 'sort', 'exactness', 'release_date:desc'],
//                                'searchableAttributes' => ['title', 'overview', 'genres', 'release_date.year'],
//                                'sortableAttributes' => ['price', 'author.surname'],
//                                'stopWords' => ['list' => ['of', 'the', 'to']],
//                                'synonyms' => ['Résumé' => ['CV'], 'CV' => ['Résumé'], 'world of warcraft' => 'wow', 'wolverine' => ['xmen', 'logan']],
//                                'typoTolerance' => [
//                                    'enabled' => true,
//                                    'minWordSizeForTypos' => ['oneTypo' => 5, 'twoTypos' => 9],
//                                    'disableOnWords' => ['hey'],
//                                    'disableOnAttributes' => ['release_date'],
//                                ],
//                            ],
//                        ],
//                    ],
//                ],
//                [
//                    'prefix' => 'sf_',
//                    'indices' => [
//                        [
//                            'name' => 'items',
//                            'class' => 'App\Entity\Post',
//                            'enable_serializer_groups' => false,
//                            'serializer_groups' => ['searchable'],
//                            'index_if' => null,
//                            'settings' => [
//                                'displayedAttributes' => ['title', 'overview', 'genres', 'release_date.year'],
//                                'distinctAttribute' => 'skuid',
//                                'faceting' => ['maxValuesPerFacet' => 100],
//                                'filterableAttributes' => ['genres', 'director', 'release_date.year'],
//                                'pagination' => ['maxTotalHits' => 1000],
//                                'rankingRules' => ['words', 'typo', 'proximity', 'attribute', 'sort', 'exactness', 'release_date:desc'],
//                                'searchableAttributes' => ['title', 'overview', 'genres', 'release_date.year'],
//                                'sortableAttributes' => ['price', 'author.surname'],
//                                'stopWords' => ['list' => ['of', 'the', 'to'], 'enabled' => true],
//                                'synonyms' => ['Résumé' => ['CV'], 'CV' => ['Résumé'], 'world of warcraft' => ['wow'], 'wolverine' => ['xmen', 'logan']],
//                                'typoTolerance' => [
//                                    'enabled' => true,
//                                    'minWordSizeForTypos' => ['oneTypo' => 5, 'twoTypos' => 9],
//                                    'disableOnWords' => ['hey'],
//                                    'disableOnAttributes' => ['release_date'],
//                                ],
//                            ],
//                        ],
//                    ],
//                    'nbResults' => 20,
//                    'batchSize' => 500,
//                    'serializer' => 'serializer',
//                    'doctrineSubscribedEvents' => ['postPersist', 'postUpdate', 'preRemove'],
//                ],
//            ],
        ];
    }
}
