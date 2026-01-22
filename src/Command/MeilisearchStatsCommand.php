<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\Command;

use Meilisearch\Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zenstruck\Bytes;

#[AsCommand(name: 'meilisearch:stats', description: 'Outputs meilisearch stats')]
final class MeilisearchStatsCommand extends Command
{
    public function __construct(
        private readonly Client $searchClient,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $stats = $this->searchClient->stats();

        $table = $io->createTable();
        $table->setStyle('box');

        $table->setHeaderTitle('Global stats');
        $table->setHeaders(['Database size', 'Used database size', 'Fragmentation ratio', 'Last update']);
        $table->addRow([
            new Bytes($stats['databaseSize']),
            new Bytes($stats['usedDatabaseSize']),
            round((($stats['databaseSize'] - $stats['usedDatabaseSize']) / $stats['databaseSize']) * 100, 2).'%',
            (new \DateTimeImmutable($stats['lastUpdate']))->format('Y M y, H:i:s.u'),
        ]);
        $table->render();
        $output->writeln('');

        $t2 = $io->createTable();
        $t2->setStyle('box');
        $t2->setHeaderTitle('Indexes stats');
        $t2->setHeaders(['Index', 'No of documents', 'Document DB size', 'Avg document size', 'Indexing', 'No of embeddings', 'No of embedded documents']);

        foreach ($stats['indexes'] as $name => $index) {
            $t2->addRow([
                $name,
                number_format($index['numberOfDocuments']),
                new Bytes($index['rawDocumentDbSize']),
                new Bytes($index['avgDocumentSize']),
                $index['isIndexing'] ? 'Yes' : 'No',
                number_format($index['numberOfEmbeddings']),
                number_format($index['numberOfEmbeddedDocuments']),
            ]);
        }
        $t2->render();

        return 0;
    }
}
