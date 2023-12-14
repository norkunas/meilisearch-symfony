<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\Command;

use Meilisearch\Bundle\Collection;
use Meilisearch\Bundle\EventListener\ConsoleOutputSubscriber;
use Meilisearch\Bundle\Model\Aggregator;
use Meilisearch\Bundle\SearchService;
use Meilisearch\Bundle\Services\MeilisearchIndexCreator;
use Meilisearch\Bundle\Services\MeilisearchSettingUpdater;
use Meilisearch\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class MeilisearchCreateCommand extends IndexCommand
{
    private Client $searchClient;
    private MeilisearchIndexCreator $indexCreator;
    private MeilisearchSettingUpdater $settingUpdater;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        Collection $configuration,
        SearchService $searchService,
        Client $searchClient,
        MeilisearchIndexCreator $indexCreator,
        MeilisearchSettingUpdater $settingUpdater,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($configuration, $searchService);

        $this->searchClient = $searchClient;
        $this->indexCreator = $indexCreator;
        $this->settingUpdater = $settingUpdater;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getDefaultName(): string
    {
        return 'meilisearch:create|meili:create';
    }

    public static function getDefaultDescription(): string
    {
        return 'Create indexes';
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->eventDispatcher->addSubscriber(new ConsoleOutputSubscriber(new SymfonyStyle($input, $output)));

        $indexes = $this->getEntitiesFromArgs($input, $output);
        $entitiesToIndex = $this->entitiesToIndex($indexes);
        $updateSettings = $input->getOption('update-settings');

        foreach ($entitiesToIndex as $index) {
            $this->indexCreator->create($index['name']);

            $task = $this->searchClient->createIndex($index['prefixed_name']);
            $this->searchClient->waitForTask($task['taskUid']);

            if ($updateSettings) {
                $this->settingUpdater->update($index['name']);
            }
        }

        $output->writeln('<info>Done!</info>');

        return 0;
    }

    private function entitiesToIndex($indexes): array
    {
        $prefix = $this->configuration->get('prefix');

        foreach ($indexes as $key => $index) {
            $entityClassName = $index['class'];

            if (is_subclass_of($entityClassName, Aggregator::class)) {
                $indexes->forget($key);

                $indexes = new Collection(array_merge(
                    $indexes->all(),
                    array_map(
                        static fn ($entity) => ['name' => $index['name'], 'prefixed_name' => $prefix.$index['name'], 'class' => $entity],
                        $entityClassName::getEntities()
                    )
                ));
            }
        }

        return array_unique($indexes->all(), SORT_REGULAR);
    }
}
