<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\DataProvider;

use Doctrine\Persistence\ManagerRegistry;

final class OrmEntityProvider implements DataProviderInterface
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
        $sortByAttrs = array_combine($entityIdentifiers, array_fill(0, \count($entityIdentifiers), 'ASC'));

        return $repository->findBy([], $sortByAttrs, $limit, $offset);
    }

    public function loadByIdentifiers(array $identifiers): array
    {
        $manager = $this->managerRegistry->getManagerForClass($this->className);
        $repository = $manager->getRepository($this->className);

        // @todo: get id field value from doctrine class metadata. and what about composite id?
        return $repository->findBy(['id' => $identifiers]);
    }

    public function getIdentifierValues(object $object): array
    {
        $manager = $this->managerRegistry->getManagerForClass(get_class($object));

        return $manager->getClassMetadata(get_class($object))->getIdentifierValues($object);
    }

    public function cleanup(): void
    {
        $this->managerRegistry->getManagerForClass($this->className)->clear();
    }
}
