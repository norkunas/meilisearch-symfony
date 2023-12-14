<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\EventListener;

use Meilisearch\Bundle\Event\BatchesSkippedEvent;
use Meilisearch\Bundle\Event\BatchIndexedEvent;
use Meilisearch\Bundle\Event\BeforeIndexImportEvent;
use Meilisearch\Bundle\Event\CreatingIndexEvent;
use Meilisearch\Bundle\Event\ImportCompletedEvent;
use Meilisearch\Bundle\Event\SettingsUpdatedEvent;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ConsoleOutputSubscriber implements EventSubscriberInterface
{
    private OutputStyle $io;

    public function __construct(OutputStyle $io)
    {
        $this->io = $io;
    }

    public function beforeCreateIndex(CreatingIndexEvent $event): void
    {
        $this->io->writeln('<info>Creating index '.$event->getIndex().' for '.$event->getClass().'</info>');
    }

    public function beforeIndexImport(BeforeIndexImportEvent $event): void
    {
        $this->io->writeln('<info>Importing for index '.$event->getClassName().'</info>');
    }

    public function afterBatchIndex(BatchIndexedEvent $event): void
    {
        $this->io->writeln(sprintf(
            'Indexed a batch of <comment>%d / %d</comment> %s entities into %s index (%d indexed since start)',
            $event->getNumberOfRecords(),
            $event->getEntities(),
            $event->getClassName(),
            '<info>'.$event->getIndex().'</info>',
            $event->getTotalIndexed(),
        ));
    }

    public function onSkippedBatches(BatchesSkippedEvent $event): void
    {
        $this->io->writeln(sprintf(
            '<info>Skipping first <comment>%d</comment> batches (<comment>%d</comment> records)</info>',
            $event->getNumberOfBatches(),
            $event->getNumberOfRecords(),
        ));
    }

    public function afterSettingsUpdate(SettingsUpdatedEvent $event): void
    {
        $this->io->writeln('<info>Settings updated of "'.$event->getIndex().'".</info>');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CreatingIndexEvent::class => 'beforeCreateIndex',
            BeforeIndexImportEvent::class => 'beforeIndexImport',
            BatchIndexedEvent::class => 'afterBatchIndex',
            BatchesSkippedEvent::class => 'onSkippedBatches',
            SettingsUpdatedEvent::class => 'afterSettingsUpdate',
        ];
    }
}
