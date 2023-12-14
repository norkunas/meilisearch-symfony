<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\Command;

use Meilisearch\Bundle\Collection;
use Meilisearch\Bundle\EventListener\ConsoleOutputSubscriber;
use Meilisearch\Bundle\SearchService;
use Meilisearch\Bundle\Services\MeilisearchImporter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class MeilisearchImportCommand extends IndexCommand
{
    private MeilisearchImporter $importer;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(Collection $configuration, SearchService $searchService, MeilisearchImporter $importer, EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct($configuration, $searchService);

        $this->importer = $importer;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getDefaultName(): string
    {
        return 'meilisearch:import|meili:import';
    }

    public static function getDefaultDescription(): string
    {
        return 'Import given entity into search engine';
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
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->eventDispatcher->addSubscriber(new ConsoleOutputSubscriber(new SymfonyStyle($input, $output)));

        $indices = array_filter(explode(',', $input->getOption('indices') ?? ''));
        $batchSize = (int) $input->getOption('batch-size');
        $skipBatches = (int) $input->getOption('skip-batches');
        $timeout = (int) $input->getOption('response-timeout');
        $updateSettings = $input->getOption('update-settings');

        $this->importer->import(
            $indices,
            $batchSize > 0 ? $batchSize : null,
            $skipBatches > 0 ? $skipBatches : null,
            $timeout > 0 ? $timeout : null,
            $updateSettings,
        );

        $output->writeln('<info>Done!</info>');

        return 0;
    }
}
