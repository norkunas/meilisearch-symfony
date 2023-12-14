<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\Command;

use Meilisearch\Bundle\Collection;
use Meilisearch\Bundle\DependencyInjection\Configuration;
use Meilisearch\Bundle\SearchService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class IndexCommand extends Command
{
    protected Collection $configuration;
    protected SearchService $searchService;

    public function __construct(Collection $configuration, SearchService $searchService)
    {
        $this->configuration = $configuration;
        $this->searchService = $searchService;

        parent::__construct();
    }

    protected function getEntitiesFromArgs(InputInterface $input, OutputInterface $output): Collection
    {
        $indices = new Collection($this->configuration->get('indices'));
        $indexNames = new Collection();

        if ($indexList = $input->getOption('indices')) {
            $prefix = $this->configuration->get('prefix');
            $list = \explode(',', $indexList);
            $indexNames = (new Collection($list))->transform(function (string $item) use ($prefix): string {
                // Check if the given index name already contains the prefix
                if (!str_starts_with($item, $prefix)) {
                    return $prefix.$item;
                }

                return $item;
            });
        }

        if (0 === count($indexNames) && 0 === count($indices)) {
            $output->writeln(
                '<comment>No indices specified. Please either specify indices using the cli option or YAML configuration.</comment>'
            );

            return new Collection();
        }

        if (count($indexNames) > 0) {
            return $indices->reject(fn (array $item) => !in_array($item['name'], $indexNames->all(), true));
        }

        return $indices;
    }
}
