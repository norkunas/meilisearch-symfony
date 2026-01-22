<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\Tests\Integration\Command;

use Meilisearch\Bundle\Tests\BaseKernelTestCase;
use Meilisearch\Bundle\Tests\Entity\Post;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class MeilisearchStatsCommandTest extends BaseKernelTestCase
{
    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = new Application(self::createKernel());
    }

    public function testImportWithoutUpdatingSettings(): void
    {
        for ($i = 0; $i <= 5; ++$i) {
            $publishedAt = new \DateTimeImmutable("2026-01-22 10:36:0$i");

            $this->entityManager->persist(new Post(publishedAt: $publishedAt));
        }

        $this->entityManager->flush();

        $importCommand = $this->application->find('meilisearch:import');
        $importCommandTester = new CommandTester($importCommand);
        $importCommandTester->execute(['--indices' => 'posts', '--no-update-settings' => true]);

        $statsCommand = $this->application->find('meilisearch:stats');
        $statsCommandTester = new CommandTester($statsCommand);
        $statsCommandTester->execute([]);

        echo $statsCommandTester->getDisplay();
    }
}
