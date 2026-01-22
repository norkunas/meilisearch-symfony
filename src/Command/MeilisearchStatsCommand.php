<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\Command;

use Meilisearch\Client;
use Meilisearch\Contracts\IndexStats;
use Meilisearch\Contracts\Stats;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Zenstruck\Bytes;
use function array_map;
use function round;

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
        if ($stats instanceof Stats) {
            $data = self::statsToArray($stats);
        } else {
            \assert(\is_array($stats));
            $data = $stats;
            if ($data['lastUpdate'] !== null) {
                $data['lastUpdate'] = new \DateTimeImmutable($data['lastUpdate']);
            }
        }

        $fragmentationRatio = round((($data['databaseSize'] - $data['usedDatabaseSize']) / $data['databaseSize']) * 100, 2);

        $t1 = $io->createTable();
        $t1->setStyle('box');

        $t1->setHeaderTitle('Global stats');
        $t1->setHeaders(['Database size', 'Used database size', 'Fragmentation ratio', 'Last update']);
        $t1->addRow([
            new Bytes($data['databaseSize']),
            new Bytes($data['usedDatabaseSize']),
            "{$fragmentationRatio}%",
            $data['lastUpdate']?->format('Y M y, H:i:s.u') ?? 'N/A',
        ]);
        $t1->render();
        $output->writeln('');

        $t2 = $io->createTable();
        $t2->setStyle('box');
        $t2->setHeaderTitle('Indexes stats');
        $t2->setHeaders(['Index', 'No of documents', 'Document DB size', 'Avg document size', 'Indexing', 'No of embeddings', 'No of embedded documents']);

        foreach ($data['indexes'] as $name => $index) {
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

        if ($fragmentationRatio >= 30) {
            $io->caution('Your Meilisearch database fragmentation ratio has reached 30%. Run `meilisearch:compact` command to compact your indexes.');
        }

        return 0;
    }

    private static function statsToArray(Stats $stats): array
    {
        return [
            'databaseSize' => $stats->getDatabaseSize(),
            'usedDatabaseSize' => $stats->getUsedDatabaseSize(),
            'lastUpdate' => $stats->getLastUpdate(),
            'indexes' => array_map(static function (IndexStats $v) {
                return [
                    'numberOfDocuments' => $v->getNumberOfDocuments(),
                    'rawDocumentDbSize' => $v->getRawDocumentDbSize(),
                    'avgDocumentSize' => $v->getAvgDocumentSize(),
                    'isIndexing' => $v->isIndexing(),
                    'numberOfEmbeddings' => $v->getNumberOfEmbeddings(),
                    'numberOfEmbeddedDocuments' => $v->getNumberOfEmbeddedDocuments(),
                    'fieldDistribution' => $v->getFieldDistribution(),
                ];
            }, $stats->getIndexes()),
        ];
    }
}
