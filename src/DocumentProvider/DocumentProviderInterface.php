<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\DocumentProvider;

/**
 * @template T of object
 */
interface DocumentProviderInterface
{
    /**
     * @param positive-int     $limit
     * @param non-negative-int $offset
     *
     * @return array<T>
     */
    public function provide(int $limit, int $offset): array;

    /**
     * @param array<mixed> $identifier
     *
     * @return T|null
     */
    public function loadByIdentifiers($identifiers);

    public function cleanup(): void;
}
