<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class BatchesSkippedEvent extends Event
{
    /**
     * @var positive-int
     */
    private int $numberOfBatches;

    /**
     * @var positive-int
     */
    private int $numberOfRecords;

    /**
     * @param positive-int $numberOfBatches
     * @param positive-int $numberOfRecords
     */
    public function __construct(
        int $numberOfBatches,
        int $numberOfRecords
    ) {
        $this->numberOfBatches = $numberOfBatches;
        $this->numberOfRecords = $numberOfRecords;
    }

    /**
     * @return positive-int
     */
    public function getNumberOfBatches(): int
    {
        return $this->numberOfBatches;
    }

    /**
     * @return positive-int
     */
    public function getNumberOfRecords(): int
    {
        return $this->numberOfRecords;
    }
}
