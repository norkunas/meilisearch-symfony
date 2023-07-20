<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\Command;

use Meilisearch\Bundle\Collection;
use Meilisearch\Bundle\DocumentProvider\DocumentProviderInterface;
use Meilisearch\Bundle\EventListener\ConsoleOutputSubscriber;
use Meilisearch\Bundle\Exception\TaskException;
use Meilisearch\Bundle\Model\Aggregator;
use Meilisearch\Bundle\SearchService;
use Meilisearch\Bundle\Services\MeilisearchManager;
use Meilisearch\Bundle\Services\SettingsUpdater;
use Meilisearch\Client;
use Meilisearch\Exceptions\TimeOutException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ImportCommand extends IndexCommand
{
    private const TEMP_INDEX_PREFIX = '_tmp_';

    private Client $searchClient;
    private SettingsUpdater $settingsUpdater;
    private EventDispatcherInterface $eventDispatcher;
    private MeilisearchManager $manager;

    public function __construct(SearchService $searchService, Client $searchClient, SettingsUpdater $settingsUpdater, EventDispatcherInterface $eventDispatcher, MeilisearchManager $manager)
    {
        parent::__construct($searchService);

        $this->searchClient = $searchClient;
        $this->settingsUpdater = $settingsUpdater;
        $this->eventDispatcher = $eventDispatcher;
        $this->manager = $manager;
    }

    public static function getDefaultName(): string
    {
        return 'meilisearch:imp';
    }

    public static function getDefaultDescription(): string
    {
        return 'Import documents into search engine';
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::getDefaultDescription())
            ->addOption('indices', 'i', InputOption::VALUE_OPTIONAL, 'Comma-separated list of index names')
            ->addOption(
                'update-settings',
                null,
                InputOption::VALUE_NEGATABLE,
                'Update settings related to indices to the search engine',
                true
            )
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED)
            ->addOption(
                'skip-batches',
                null,
                InputOption::VALUE_REQUIRED,
                'Skip the first N batches and start importing from the N+1 batch',
                0
            )
            ->addOption(
                'response-timeout',
                't',
                InputOption::VALUE_REQUIRED,
                'Timeout (in ms) to get response from the search engine',
                self::DEFAULT_RESPONSE_TIMEOUT
            )
            ->addOption(
                'swap-indices',
                null,
                InputOption::VALUE_NONE,
                'Import to temporary indices and use index swap to prevent downtime'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->eventDispatcher->addSubscriber(new ConsoleOutputSubscriber(new SymfonyStyle($input, $output)));

        $indexes = $this->getEntitiesFromArgs($input, $output);
        $entitiesToIndex = $this->entitiesToIndex($indexes);
        $config = $this->searchService->getConfiguration();
        $updateSettings = $input->getOption('update-settings');
        $batchSize = $input->getOption('batch-size') ?? '';
        $batchSize = is_string($batchSize) && ctype_digit($batchSize) ? (int) $batchSize : $config->get('batchSize');
        $swapIndices = $input->getOption('swap-indices');
        $responseTimeout = ((int) $input->getOption('response-timeout')) ?: self::DEFAULT_RESPONSE_TIMEOUT;
        $initialPrefix = $config['prefix'] ?? '';
        $prefix = null;

        if ($swapIndices) {
            $prefix = self::TEMP_INDEX_PREFIX;
            $config['prefix'] = $prefix.($config['prefix'] ?? '');
        }

        /** @var array $index */
        foreach ($entitiesToIndex as $index) {
            $entityClassName = $index['class'];

            if (!$this->manager->isSearchable($entityClassName)) {
                continue;
            }

            $totalIndexed = 0;

            $output->writeln('<info>Importing for index '.$entityClassName.'</info>');

            $page = max(0, (int) $input->getOption('skip-batches'));

            if ($page > 0) {
                $output->writeln(
                    \sprintf(
                        '<info>Skipping first <comment>%d</comment> batches (<comment>%d</comment> records)</info>',
                        $page,
                        $page * $batchSize,
                    )
                );
            }

            do {
                $documentProvider = $this->manager->getDocumentProvider($index['name']);

                $objects = $documentProvider->provide($batchSize, $batchSize * $page);
                $indexingResponse = $this->manager->index($objects);
                $responses = $this->formatIndexingResponse($indexingResponse, $responseTimeout);
                $totalIndexed += \count($objects);

                foreach ($responses as $indexName => $numberOfRecords) {
                    $output->writeln(
                        \sprintf(
                            'Indexed a batch of <comment>%d / %d</comment> %s entities into %s index (%d indexed since start)',
                            $numberOfRecords,
                            \count($objects),
                            $entityClassName,
                            '<info>'.$indexName.'</info>',
                            $totalIndexed,
                        )
                    );
                }

                $documentProvider->cleanup();

                ++$page;
            } while (count($objects) >= $batchSize);

            if ($updateSettings) {
                $this->settingsUpdater->update($index['prefixed_name'], $responseTimeout, $prefix ? $prefix.$index['prefixed_name'] : null);
            }
        }

        if ($swapIndices) {
            $this->swapIndices($indexes, $prefix, $output);

            $config['prefix'] = $initialPrefix;
        }

        $output->writeln('<info>Done!</info>');

        return 0;
    }

    /**
     * @throws TimeOutException
     */
    private function formatIndexingResponse(array $batch, int $responseTimeout): array
    {
        $formattedResponse = [];

        foreach ($batch as $chunk) {
            foreach ($chunk as $indexName => $apiResponse) {
                $formattedResponse[$indexName] ??= 0;
                $indexInstance = $this->searchClient->index($indexName);

                $indexInstance->waitForTask($apiResponse['taskUid'], $responseTimeout);
                $task = $indexInstance->getTask($apiResponse['taskUid']);

                if ('failed' === $task['status']) {
                    throw new TaskException($task['error']['message']);
                }

                $formattedResponse[$indexName] += $task['details']['indexedDocuments'];
            }
        }

        return $formattedResponse;
    }

    private function entitiesToIndex($indexes): array
    {
        foreach ($indexes as $key => $index) {
            $entityClassName = $index['class'];

            if (!is_subclass_of($entityClassName, Aggregator::class)) {
                continue;
            }

            $indexes->forget($key);

            $indexes = new Collection(array_merge(
                $indexes->all(),
                array_map(
                    static fn ($entity) => ['name' => $index['name'], 'prefixed_name' => $index['prefixed_name'], 'class' => $entity],
                    $entityClassName::getEntities()
                )
            ));
        }

        return array_unique($indexes->all(), SORT_REGULAR);
    }

    private function swapIndices(Collection $indexes, string $prefix, OutputInterface $output): void
    {
        $indexPairs = [];

        foreach ($indexes as $index) {
            $tempIndex = $index;
            $tempIndex['name'] = $prefix.$tempIndex['name'];
            $pair = [$tempIndex['name'], $index['name']];

            // Indexes must be declared only once during a swap
            if (!\in_array($pair, $indexPairs, true)) {
                $indexPairs[] = $pair;
            }
        }

        // swap indexes
        $output->writeln('<info>Swapping indices...</info>');
        $this->searchClient->swapIndexes($indexPairs);
        $output->writeln('<info>Indices swapped.</info>');
        $output->writeln('<info>Deleting temporary indices...</info>');

        // delete temp indexes
        foreach ($indexPairs as $pair) {
            $this->searchService->deleteByIndexName($pair[0]);
            $output->writeln('<info>Deleted '.$pair[0].'</info>');
        }
    }
}
