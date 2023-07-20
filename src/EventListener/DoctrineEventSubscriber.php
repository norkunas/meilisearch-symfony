<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Meilisearch\Bundle\SearchService;
use Meilisearch\Bundle\Services\MeilisearchManager;

final class DoctrineEventSubscriber
{
    private SearchService $searchService;
    private MeilisearchManager $manager;

    public function __construct(SearchService $searchService, MeilisearchManager $manager)
    {
        $this->searchService = $searchService;
        $this->manager = $manager;
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        //$this->manager->index($args->getObject());
        //$this->searchService->index($args->getObjectManager(), $args->getObject());
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        //$this->manager->index($args->getObject());
        //$this->searchService->index($args->getObjectManager(), $args->getObject());
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        //$this->manager->remove($args->getObject());
        //$this->searchService->remove($args->getObjectManager(), $args->getObject());
    }
}
