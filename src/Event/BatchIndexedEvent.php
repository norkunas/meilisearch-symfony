<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class BatchIndexedEvent extends Event
{
    /**
     * @var class-string
     */
    private string $className;

    /**
     * @var non-empty-string
     */
    private string $index;

    /**
     * @var positive-int
     */
    private int $numberOfRecords;

    /**
     * @var positive-int
     */
    private int $entities;

    /**
     * @var positive-int
     */
    private int $totalIndexed;

    /**
     * @param class-string     $className
     * @param non-empty-string $index
     * @param positive-int     $numberOfRecords
     * @param positive-int     $entities
     * @param positive-int     $totalIndexed
     */
    public function __construct(
        string $className,
        string $index,
        int $numberOfRecords,
        int $entities,
        int $totalIndexed
    ) {
        $this->className = $className;
        $this->index = $index;
        $this->numberOfRecords = $numberOfRecords;
        $this->entities = $entities;
        $this->totalIndexed = $totalIndexed;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getIndex(): string
    {
        return $this->index;
    }

    public function getNumberOfRecords(): int
    {
        return $this->numberOfRecords;
    }

    public function getEntities(): int
    {
        return $this->entities;
    }

    public function getTotalIndexed(): int
    {
        return $this->totalIndexed;
    }
}
