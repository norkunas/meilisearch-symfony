<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\Services;

use Doctrine\ORM\Proxy\DefaultProxyClassNameResolver;
use Meilisearch\Bundle\Collection;
use Meilisearch\Bundle\DataProvider\DataProviderInterface;
use Meilisearch\Bundle\Engine;
use Meilisearch\Bundle\Entity\Aggregator;
use Meilisearch\Bundle\Exception\ObjectIdNotFoundException;
use Meilisearch\Bundle\Exception\SearchHitsNotFoundException;
use Meilisearch\Bundle\SearchableObject;
use Psr\Container\ContainerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class MeilisearchManager
{
    private const RESULT_KEY_HITS = 'hits';
    private const RESULT_KEY_OBJECTID = 'objectID';

    private NormalizerInterface $normalizer;

    private Engine $engine;

    private Collection $configuration;

    private PropertyAccessorInterface $propertyAccessor;

    private ContainerInterface $dataProviders;

    /**
     * @var list<class-string>
     */
    private array $searchables;

    /**
     * @var array<class-string, list<class-string>>
     */
    private array $entitiesAggregators;

    /**
     * @var list<class-string<Aggregator>>
     */
    private array $aggregators;

    /**
     * @var array<class-string, array<string>>
     */
    private array $classToSerializerGroup;

    private array $indexIfMapping;

    public function __construct(NormalizerInterface $normalizer, Engine $engine, PropertyAccessorInterface $propertyAccessor, array $configuration, ContainerInterface $dataProviders)
    {
        $this->normalizer = $normalizer;
        $this->engine = $engine;
        $this->propertyAccessor = $propertyAccessor;
        $this->configuration = new Collection($configuration);
        $this->dataProviders = $dataProviders;

        $this->setSearchables();
        $this->setAggregatorsAndEntitiesAggregators();
        $this->setClassToSerializerGroup();
        $this->setIndexIfMapping();
    }

    public function isSearchable($className): bool
    {
        $className = $this->getBaseClassName($className);

        return \in_array($className, $this->searchables, true);
    }

    public function getSearchables(): array
    {
        return $this->searchables;
    }

    public function getConfiguration(): Collection
    {
        return $this->configuration;
    }

    public function getDataProvider(string $indice): DataProviderInterface
    {
        return $this->dataProviders->get($indice);
    }

    public function searchableAs(string $className): string
    {
        $className = $this->getBaseClassName($className);

        $indexes = new Collection($this->getConfiguration()->get('indices'));
        $index = $indexes->firstWhere('class', $className);

        return $this->getConfiguration()->get('prefix').$index['name'];
    }

    /**
     * @param object|array<object> $searchable
     */
    public function index($searchable): array
    {
        $searchable = \is_array($searchable) ? $searchable : [$searchable];
        $searchable = array_merge($searchable, $this->getAggregatorsFromEntities($searchable));

        $dataToIndex = array_filter(
            $searchable,
            fn ($entity) => $this->isSearchable($entity)
        );

        $dataToRemove = [];
        foreach ($dataToIndex as $key => $entity) {
            if (!$this->shouldBeIndexed($entity)) {
                unset($dataToIndex[$key]);
                $dataToRemove[] = $entity;
            }
        }

        if (\count($dataToRemove) > 0) {
            $this->remove($dataToRemove);
        }

        return $this->makeSearchServiceResponseFrom(
            $dataToIndex,
            fn (array $chunk) => $this->engine->index($chunk)
        );
    }

    /**
     * @param object|array<object> $searchable
     */
    public function remove($searchable): array
    {
        $searchable = \is_array($searchable) ? $searchable : [$searchable];
        $searchable = array_merge($searchable, $this->getAggregatorsFromEntities($searchable));
        $dataToIndex = array_filter($searchable, fn ($entity) => $this->isSearchable($entity));

        return $this->makeSearchServiceResponseFrom($dataToIndex, fn ($chunk) => $this->engine->remove($chunk));
    }

    /**
     * @param class-string $className
     */
    public function clear(string $className): array
    {
        $this->assertIsSearchable($className);

        return $this->engine->clear($this->searchableAs($className));
    }

    /**
     * @param non-empty-string $indexName
     */
    public function deleteByIndexName(string $indexName): array
    {
        return $this->engine->delete($indexName);
    }

    /**
     * @param class-string $className
     */
    public function delete(string $className): array
    {
        $this->assertIsSearchable($className);

        return $this->engine->delete($this->searchableAs($className));
    }

    /**
     * @param class-string $className
     */
    public function search(
        string $className,
        string $query = '',
        array $searchParams = []
    ): array {
        $this->assertIsSearchable($className);

        $response = $this->engine->search($query, $this->searchableAs($className), $searchParams + ['limit' => $this->configuration['nbResults']]);
        $results = [];

        // Check if the engine returns results in "hits" key
        if (!isset($response[self::RESULT_KEY_HITS])) {
            throw new SearchHitsNotFoundException(\sprintf('There is no "%s" key in the search results.', self::RESULT_KEY_HITS));
        }

        // temporary
        $className = $this->getBaseClassName($className);

        $indexes = new Collection($this->getConfiguration()->get('indices'));
        $index = $indexes->firstWhere('class', $className);
        //
        $dataProvider=$this->getDataProvider($index['name']);

        $identifiers = array_column($response[self::RESULT_KEY_HITS], self::RESULT_KEY_OBJECTID);

        //dump($this->searchableAs($className),$response);
        // @todo: complete

        $loaded=$dataProvider->loadByIdentifiers($identifiers);

        foreach ($response[self::RESULT_KEY_HITS] as $hit) {
            $documentId = $hit[self::RESULT_KEY_OBJECTID];

            $obj = self::find($loaded, static function ($object) use ($documentId) {
                return $object->getId() === $documentId;
            });
            if ($obj !== null) {
                $results[]=$obj;
            }
        }

        //        foreach ($ids[self::RESULT_KEY_HITS] as $hit) {
        //            if (!isset($hit[self::RESULT_KEY_OBJECTID])) {
        //                throw new ObjectIdNotFoundException(sprintf('There is no "%s" key in the result.', self::RESULT_KEY_OBJECTID));
        //            }
        //
        //            $documentId = $hit[self::RESULT_KEY_OBJECTID];
        //            $entityClass = $className;
        //
        //            if (in_array($className, $this->aggregators, true)) {
        //                $objectId = $hit[self::RESULT_KEY_OBJECTID];
        //                $entityClass = $className::getEntityClassFromObjectId($objectId);
        //                $documentId = $className::getEntityIdFromObjectId($objectId);
        //            }
        //
        //            $repo = $objectManager->getRepository($entityClass);
        //            $entity = $repo->find($documentId);
        //
        //            if (null !== $entity) {
        //                $results[] = $entity;
        //            }
        //        }
        //
        return $results;
    }
    private static function find(array $objects, $callback) // @todo: remove me
    {
        foreach ($objects as $object) {
            if ($callback($object)) {
                return $object;
            }
        }

        return null;
    }

    public function rawSearch(
        string $className,
        string $query = '',
        array $searchParams = []
    ): array {
        $this->assertIsSearchable($className);

        return $this->engine->search($query, $this->searchableAs($className), $searchParams);
    }

    public function count(string $className, string $query = '', array $searchParams = []): int
    {
        $this->assertIsSearchable($className);

        return $this->engine->count($query, $this->searchableAs($className), $searchParams);
    }

    public function shouldBeIndexed(object $entity): bool
    {
        $className = $this->getBaseClassName($entity);

        $propertyPath = $this->indexIfMapping[$className];

        if (null !== $propertyPath) {
            if ($this->propertyAccessor->isReadable($entity, $propertyPath)) {
                return (bool) $this->propertyAccessor->getValue($entity, $propertyPath);
            }

            return false;
        }

        return true;
    }

    /**
     * @param object|class-string $objectOrClass
     *
     * @return class-string
     */
    private function getBaseClassName($objectOrClass): string
    {
        foreach ($this->searchables as $class) {
            if (is_a($objectOrClass, $class, true)) {
                return $class;
            }
        }

        if (\is_object($objectOrClass)) {
            return self::resolveClass($objectOrClass);
        }

        return $objectOrClass;
    }

    private function setSearchables(): void
    {
        $this->searchables = array_unique(array_column($this->configuration->get('indices'), 'class'));
        //        $searchable = [];
        //
        //        foreach ($this->configuration->get('indices') as $index) {
        //            $searchable[] = $index['class'];
        //        }
        //        $this->searchables = array_unique($searchable);
    }

    private function setAggregatorsAndEntitiesAggregators(): void
    {
        $this->entitiesAggregators = [];
        $this->aggregators = [];

        foreach ($this->configuration->get('indices') as $index) {
            if (is_subclass_of($index['class'], Aggregator::class)) {
                foreach ($index['class']::getEntities() as $entityClass) {
                    if (!isset($this->entitiesAggregators[$entityClass])) {
                        $this->entitiesAggregators[$entityClass] = [];
                    }

                    $this->entitiesAggregators[$entityClass][] = $index['class'];
                    $this->aggregators[] = $index['class'];
                }
            }
        }

        $this->aggregators = array_unique($this->aggregators);
    }

    private function setClassToSerializerGroup(): void
    {
        $mapping = [];

        /** @var array $indexDetails */
        foreach ($this->configuration->get('indices') as $indexDetails) {
            $mapping[$indexDetails['class']] = $indexDetails['enable_serializer_groups'] ? $indexDetails['serializer_groups'] : [];
        }
        $this->classToSerializerGroup = $mapping;
    }

    private function setIndexIfMapping(): void
    {
        $mapping = [];

        /** @var array $indexDetails */
        foreach ($this->configuration->get('indices') as $indexDetails) {
            $mapping[$indexDetails['class']] = $indexDetails['index_if'];
        }
        $this->indexIfMapping = $mapping;
    }

    /**
     * Returns the aggregators instances of the provided entities.
     *
     * @param array<int, object> $entities
     *
     * @return array<int, Aggregator>
     */
    private function getAggregatorsFromEntities(array $entities): array
    {
        $aggregators = [];

        foreach ($entities as $entity) {
            $entityClassName = self::resolveClass($entity);

            if (array_key_exists($entityClassName, $this->entitiesAggregators)) {
                $provider = $this->getDataProviderByClass($entityClassName);

                foreach ($this->entitiesAggregators[$entityClassName] as $aggregator) {
                    $aggregators[] = new $aggregator(
                        $entity,
                        $provider->getIdentifierValues($entity),
                    );
                }
            }
        }

        return $aggregators;
    }

    /**
     * For each chunk performs the provided operation.
     *
     * @param array<int, object> $objects
     */
    private function makeSearchServiceResponseFrom(
        array $objects,
        callable $operation
    ): array {
        $batch = [];

        foreach (array_chunk($objects, $this->configuration->get('batchSize')) as $chunk) {
            $searchableChunk = [];

            foreach ($chunk as $object) {
                $objectClassName = $this->getBaseClassName($object);

                $searchableChunk[] = new SearchableObject(
                    $this->searchableAs($objectClassName),
                    $this->getPrimaryKey($objectClassName),
                    $object,
                    $this->getSingleIdentifier( // @todo: extension point?
                        $this->getDataProviderByClass($objectClassName),
                        $object,
                    ),
                    $this->normalizer,
                    ['groups' => $this->getNormalizationGroups($objectClassName)],
                );
            }

            $batch[] = $operation($searchableChunk);
        }

        return $batch;
    }

    /**
     * @param class-string $className
     *
     * @return list<string>
     */
    private function getNormalizationGroups(string $className): array
    {
        return $this->classToSerializerGroup[$className];
    }

    private function assertIsSearchable(string $className): void
    {
        if (!$this->isSearchable($className)) {
            throw new Exception('Class '.$className.' is not searchable.');
        }
    }

    /**
     * @param object|string $objectOrClass
     *
     * @return string
     */
    private function getPrimaryKey(string $objectOrClass): string
    {
        $className = $this->getBaseClassName($objectOrClass);

        $indexes = new Collection($this->getConfiguration()->get('indices'));
        $index = $indexes->firstWhere('class', $className);

        return $index['primary_key'];
    }

    private function getDataProviderByClass(string $class): DataProviderInterface
    {
        $indexes = new Collection($this->getConfiguration()->get('indices'));
        $index = $indexes->firstWhere('class', $class);

        return $this->getDataProvider($index['name']);
    }

    /**
     * @return string|int
     */
    private function getSingleIdentifier(DataProviderInterface $provider, object $object)
    {
        $ids = $provider->getIdentifierValues($object);

        if (0 === \count($ids)) {
            throw new Exception('Object has no primary key');
        }

        if (1 === \count($ids)) {
            return reset($ids);
        }

        $objectID = '';
        foreach ($ids as $key => $value) {
            $objectID .= $key.'-'.$value.'__';
        }

        return $objectID;
    }

    private static function resolveClass(object $object): string
    {
        static $resolver;

        $resolver ??= (function () {
            // Doctrine ORM v3+ compatibility
            if (class_exists(DefaultProxyClassNameResolver::class)) {
                return fn (object $object) => DefaultProxyClassNameResolver::getClass($object);
            }

            // Legacy Doctrine ORM compatibility
            return fn (object $object) => ClassUtils::getClass($object); // @codeCoverageIgnore
        })();

        return $resolver($object);
    }
}
