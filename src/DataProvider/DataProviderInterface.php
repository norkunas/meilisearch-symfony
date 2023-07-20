<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\DataProvider;

/**
 * @template T of object
 */
interface DataProviderInterface
{
    /**
     * @param positive-int     $limit
     * @param non-negative-int $offset
     *
     * @return array<T>
     */
    public function provide(int $limit, int $offset): array;

    /**
     * @param array<mixed> $identifiers
     *
     * @return array<T>|null
     */
    public function loadByIdentifiers(array $identifiers): array;

    /**
     * Returns the identifier of this object as an array with a field name as a key.
     *
     * @return non-empty-array<string, mixed>
     */
    public function getIdentifierValues(object $object): array;

    public function cleanup(): void;
}
