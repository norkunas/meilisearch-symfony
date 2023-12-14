<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\Services;

use Meilisearch\Bundle\Collection;
use Meilisearch\Bundle\Event\CreatingIndexEvent;
use Meilisearch\Bundle\Exception\InvalidIndiceException;
use Meilisearch\Client;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class MeilisearchIndexCreator
{
    private Client $searchClient;
    private EventDispatcherInterface $eventDispatcher;
    private Collection $configuration;

    public function __construct(
        Client $searchClient,
        EventDispatcherInterface $eventDispatcher,
        Collection $configuration
    ) {
        $this->searchClient = $searchClient;
        $this->eventDispatcher = $eventDispatcher;
        $this->configuration = $configuration;
    }

    /**
     * @param non-empty-string $indice
     */
    public function create(string $indice): void
    {
        $index = (new Collection($this->configuration->get('indices')))->firstWhere('name', $indice);

        if (!is_array($index)) {
            throw new InvalidIndiceException(sprintf('Meilisearch index for "%s" was not found.', $indice));
        }

        $this->eventDispatcher->dispatch(new CreatingIndexEvent($index['class'], $index['prefixed_name']));

        $task = $this->searchClient->createIndex($index['prefixed_name']);

        $this->searchClient->waitForTask($task['taskUid']);
    }
}
