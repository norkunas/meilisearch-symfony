<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\Tests\Integration\Command;

use Meilisearch\Bundle\Tests\BaseKernelTestCase;
use Meilisearch\Bundle\Tests\Entity\Post;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use function dd;

final class MeilisearchStatsCommandTest extends BaseKernelTestCase
{
    private MockHttpClient $httpClient;
    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->httpClient = new MockHttpClient();
        self::getContainer()->set('http_client', $this->httpClient);
        $this->application = new Application($kernel);
    }

    public function testStats(): void
    {
        $this->httpClient->setResponseFactory(function (string $method, string $url) {
            self::assertSame('GET', $method);
            self::assertSame('http://127.0.0.1:7700/stats', $url);

            return new JsonMockResponse([
                'databaseSize' => 1097728,
                'usedDatabaseSize' => 798720,
                'lastUpdate' => '2026-02-10T13:19:56.170442332Z',
                'indexes' => [
                    'sf_phpunit__posts' => [
                        'numberOfDocuments' => 6,
                        'rawDocumentDbSize' => 4096,
                        'avgDocumentSize' => 674,
                        'isIndexing' => false,
                        'numberOfEmbeddings' => 0,
                        'numberOfEmbeddedDocuments' => 0,
                        'fieldDistribution' => [
                            'comments' => 6,
                            'content' => 6,
                            'id' => 6,
                            'objectID' => 6,
                            'publishedAt' => 6,
                            'title' => 6,
                        ],
                    ],
                    'sf_phpunit__movies' => [
                        'numberOfDocuments' => 100,
                        'rawDocumentDbSize' => 4096,
                        'avgDocumentSize' => 900,
                        'isIndexing' => true,
                        'numberOfEmbeddings' => 99,
                        'numberOfEmbeddedDocuments' => 99,
                        'fieldDistribution' => [
                            'id' => 100,
                            'objectID' => 100,
                            'genre' => 99,
                            'title' => 100,
                        ],
                    ],
                ],
            ]);
        });

        $statsCommand = $this->application->find('meilisearch:stats');
        $statsCommandTester = new CommandTester($statsCommand);
        $statsCommandTester->execute([]);

        self::assertSame(<<<'EOD'
┌───────────────┬────────────────────┬ Global stats ───────┬──────────────────────────────┐
│ Database size │ Used database size │ Fragmentation ratio │ Last update                  │
├───────────────┼────────────────────┼─────────────────────┼──────────────────────────────┤
│ 1.10 MB       │ 798.72 kB          │ 27.24%              │ 2026 Feb 26, 13:19:56.170442 │
└───────────────┴────────────────────┴─────────────────────┴──────────────────────────────┘

┌────────────────────┬─────────────────┬──────────────────┬─ Indexes stats ───┬──────────┬──────────────────┬──────────────────────────┐
│ Index              │ No of documents │ Document DB size │ Avg document size │ Indexing │ No of embeddings │ No of embedded documents │
├────────────────────┼─────────────────┼──────────────────┼───────────────────┼──────────┼──────────────────┼──────────────────────────┤
│ sf_phpunit__posts  │ 6               │ 4.10 kB          │ 674 B             │ No       │ 0                │ 0                        │
│ sf_phpunit__movies │ 100             │ 4.10 kB          │ 900 B             │ Yes      │ 99               │ 99                       │
└────────────────────┴─────────────────┴──────────────────┴───────────────────┴──────────┴──────────────────┴──────────────────────────┘

EOD, $statsCommandTester->getDisplay());
    }

    public function testStatsWithBiggerFragmentationRatio(): void
    {
        $this->httpClient->setResponseFactory(function (string $method, string $url) {
            self::assertSame('GET', $method);
            self::assertSame('http://127.0.0.1:7700/stats', $url);

            return new JsonMockResponse([
                'databaseSize' => 1097728,
                'usedDatabaseSize' => 768400,
                'lastUpdate' => '2026-02-10T13:19:56.170442332Z',
                'indexes' => [
                    'sf_phpunit__movies' => [
                        'numberOfDocuments' => 100,
                        'rawDocumentDbSize' => 4096,
                        'avgDocumentSize' => 900,
                        'isIndexing' => true,
                        'numberOfEmbeddings' => 99,
                        'numberOfEmbeddedDocuments' => 99,
                        'fieldDistribution' => [
                            'id' => 100,
                            'objectID' => 100,
                            'genre' => 99,
                            'title' => 100,
                        ],
                    ],
                ],
            ]);
        });

        $statsCommand = $this->application->find('meilisearch:stats');
        $statsCommandTester = new CommandTester($statsCommand);
        $statsCommandTester->execute([]);

        self::assertStringContainsString(<<<'EOD'
 ! [CAUTION] Your Meilisearch database fragmentation ratio has reached 30%. Run `meilisearch:compact` command to compact
 !           your indexes.
EOD, $statsCommandTester->getDisplay());
    }
}
