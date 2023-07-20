<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Meilisearch\Bundle\Services\MeilisearchManager;

final class DoctrineEventSubscriber
{
    private MeilisearchManager $searchManager;

    public function __construct(MeilisearchManager $searchManager)
    {
        $this->searchManager = $searchManager;
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->searchManager->index($args->getObject());
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->searchManager->index($args->getObject());
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $this->searchManager->remove($args->getObject());
    }
}
