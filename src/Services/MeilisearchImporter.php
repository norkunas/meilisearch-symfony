<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\Services;

use Doctrine\Persistence\ManagerRegistry;
use Meilisearch\Bundle\Collection;
use Meilisearch\Bundle\DependencyInjection\Configuration;
use Meilisearch\Bundle\Event\BatchesSkippedEvent;
use Meilisearch\Bundle\Event\BatchIndexedEvent;
use Meilisearch\Bundle\Event\BeforeIndexImportEvent;
use Meilisearch\Bundle\Exception\TaskException;
use Meilisearch\Bundle\Model\Aggregator;
use Meilisearch\Bundle\SearchService;
use Meilisearch\Client;
use Meilisearch\Exceptions\TimeOutException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class MeilisearchImporter
{
    private Collection $configuration;
    private SearchService $searchService;
    private Client $searchClient;
    private ManagerRegistry $managerRegistry;
    private MeilisearchSettingUpdater $settingUpdater;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        Collection $configuration,
        SearchService $searchService,
        Client $searchClient,
        ManagerRegistry $managerRegistry,
        MeilisearchSettingUpdater $settingUpdater,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->configuration = $configuration;
        $this->searchService = $searchService;
        $this->managerRegistry = $managerRegistry;
        $this->searchClient = $searchClient;
        $this->settingUpdater = $settingUpdater;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param array<non-empty-string> $indices
     * @param positive-int|null       $batchSize
     * @param int<0, max>|null        $skipBatches
     * @param positive-int|null       $responseTimeout
     */
    public function import(array $indices, int $batchSize = null, int $skipBatches = null, int $responseTimeout = null, bool $updateSettings = true): void
    {
        $batchSize = $batchSize ?? $this->configuration->get('batchSize');
        $indexes = $this->getEntitiesFromArgs($indices);
        $responseTimeout = $responseTimeout ?? Configuration::DEFAULT_RESPONSE_TIMEOUT;

        foreach ($indexes as $key => $index) {
            $entityClassName = $index['class'];

            if (is_subclass_of($entityClassName, Aggregator::class)) {
                $indexes->forget($key);

                $indexes = new Collection(array_merge(
                    $indexes->all(),
                    array_map(
                        fn ($entity) => ['name' => $index['name'], 'class' => $entity],
                        $entityClassName::getEntities()
                    )
                ));
            }
        }

        $entitiesToIndex = array_unique($indexes->all(), SORT_REGULAR);

        /** @var array $index */
        foreach ($entitiesToIndex as $index) {
            $entityClassName = $index['class'];

            if (!$this->searchService->isSearchable($entityClassName)) {
                continue;
            }

            $totalIndexed = 0;

            $manager = $this->managerRegistry->getManagerForClass($entityClassName);
            $repository = $manager->getRepository($entityClassName);
            $classMetadata = $manager->getClassMetadata($entityClassName);
            $entityIdentifiers = $classMetadata->getIdentifierFieldNames();
            $sortByAttrs = array_combine($entityIdentifiers, array_fill(0, count($entityIdentifiers), 'ASC'));

            $this->eventDispatcher->dispatch(new BeforeIndexImportEvent($entityClassName, $index['name']));

            $page = $skipBatches ?? 0;

            if ($skipBatches > 0) {
                $this->eventDispatcher->dispatch(new BatchesSkippedEvent($page, $page * $batchSize));
            }

            do {
                $entities = $repository->findBy(
                    [],
                    $sortByAttrs,
                    $batchSize,
                    $batchSize * $page
                );

                $responses = $this->formatIndexingResponse($this->searchService->index($manager, $entities), $responseTimeout);
                $totalIndexed += count($entities);

                foreach ($responses as $indexName => $numberOfRecords) {
                    $this->eventDispatcher->dispatch(new BatchIndexedEvent(
                        $entityClassName,
                        $indexName,
                        $numberOfRecords,
                        \count($entities),
                        $totalIndexed,
                    ));
                }

                if ($updateSettings) {
                    $this->settingUpdater->update($index['name'], $responseTimeout);
                }

                ++$page;
            } while (count($entities) >= $batchSize);

            $manager->clear();
        }
    }

    /**
     * @param array<non-empty-string> $providedIndices
     */
    private function getEntitiesFromArgs(array $providedIndices): Collection
    {
        $indices = new Collection($this->configuration->get('indices'));
        $indexNames = new Collection();

        if ($providedIndices !== []) {
            $prefix = $this->configuration->get('prefix');
            $indexNames = (new Collection($providedIndices))->transform(function (string $item) use ($prefix): string {
                // Check if the given index name already contains the prefix
                if (!str_starts_with($item, $prefix)) {
                    return $prefix.$item;
                }

                return $item;
            });
        }

        if (0 === count($indexNames) && 0 === count($indices)) {

//            $output->writeln(
//                '<comment>No indices specified. Please either specify indices using the cli option or YAML configuration.</comment>'
//            );

            return new Collection();
        }

        if (count($indexNames) > 0) {
            return $indices->reject(fn (array $item) => !in_array($item['prefixed_name'], $indexNames->all(), true));
        }

        return $indices;
    }

    /**
     * @return array<non-empty-string, int<0, max>>
     *
     * @throws TimeOutException
     */
    private function formatIndexingResponse(array $batch, int $responseTimeout): array
    {
        $formattedResponse = [];

        foreach ($batch as $chunk) {
            foreach ($chunk as $indexName => $apiResponse) {
                if (!array_key_exists($indexName, $formattedResponse)) {
                    $formattedResponse[$indexName] = 0;
                }

                $indexInstance = $this->searchClient->index($indexName);

                // Get task information using uid
                $indexInstance->waitForTask($apiResponse['taskUid'], $responseTimeout);
                $task = $indexInstance->getTask($apiResponse['taskUid']);

                if ('failed' === $task['status']) {
                    throw new TaskException($task['error']);
                }

                $formattedResponse[$indexName] += $task['details']['indexedDocuments'];
            }
        }

        return $formattedResponse;
    }
}
