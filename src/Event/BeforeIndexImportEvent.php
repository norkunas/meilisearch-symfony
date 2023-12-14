<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class BeforeIndexImportEvent extends Event
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
     * @param class-string     $className
     * @param non-empty-string $index
     */
    public function __construct(
        string $className,
        string $index
    ) {
        $this->className = $className;
        $this->index = $index;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getIndex(): string
    {
        return $this->index;
    }
}
