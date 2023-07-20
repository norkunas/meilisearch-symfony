<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\DocumentProvider;

use Doctrine\Persistence\ManagerRegistry;

final class OrmEntityProvider implements DocumentProviderInterface
{
    private ManagerRegistry $managerRegistry;

    /**
     * @var class-string
     */
    private string $className;

    public function __construct(ManagerRegistry $managerRegistry, string $className)
    {
        $this->managerRegistry = $managerRegistry;
        $this->className = $className;
    }

    public function provide(int $limit, int $offset): array
    {
        $manager = $this->managerRegistry->getManagerForClass($this->className);
        $repository = $manager->getRepository($this->className);
        $classMetadata = $manager->getClassMetadata($this->className);
        $entityIdentifiers = $classMetadata->getIdentifierFieldNames();
        $sortByAttrs = array_combine($entityIdentifiers, array_fill(0, count($entityIdentifiers), 'ASC'));

        return $repository->findBy([], $sortByAttrs, $limit, $offset);
    }

    public function loadByIdentifiers($identifiers)
    {
        // TODO: Implement loadByIdentifiers() method.
    }

    public function cleanup(): void
    {
        $this->managerRegistry->getManagerForClass($this->className)->clear();
    }
}
