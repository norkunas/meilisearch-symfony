<?php

declare(strict_types=1);

namespace Meilisearch\Bundle;

use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class SearchableObject
{
    public const NORMALIZATION_FORMAT = 'searchableArray';
    public const NORMALIZATION_GROUP = 'searchable';

    /**
     * @var non-empty-string
     */
    private string $indexUid;

    /**
     * @var object
     */
    private $object;

    private ?NormalizerInterface $normalizer;

    /**
     * @var array<mixed>
     */
    private array $normalizationContext;

    /**
     * @var int|string
     */
    private $id;

    /**
     * @param non-empty-string $indexUid
     * @param object           $object
     * @param array<mixed>     $normalizationContext
     */
    public function __construct(
        string $indexUid,
        $object,
        NormalizerInterface $normalizer,
        array $normalizationContext = []
    ) {
        $this->indexUid = $indexUid;
        $this->object = $object;
        $this->normalizer = $normalizer;
        $this->normalizationContext = array_merge($normalizationContext, ['meilisearch' => true]);

        $this->setId();
    }

    public function getIndexUid(): string
    {
        return $this->indexUid;
    }

    /**
     * @throws ExceptionInterface
     */
    public function getSearchableArray(): array
    {
        if (Kernel::VERSION_ID >= 70100) {
            $this->normalizationContext[DateTimeNormalizer::FORMAT_KEY] = 'U';
            $this->normalizationContext[DateTimeNormalizer::CAST_KEY] = 'int';
        }

        if ($this->object instanceof NormalizableInterface) {
            return $this->object->normalize($this->normalizer, self::NORMALIZATION_FORMAT, $this->normalizationContext);
        }

        return $this->normalizer->normalize($this->object, self::NORMALIZATION_FORMAT, $this->normalizationContext);
    }

    private function setId(): void
    {
        //$ids = $this->entityMetadata->getIdentifierValues($this->entity);
        $ids = ['id' => $this->entity->getId()]; // @todo: ???

        if (0 === \count($ids)) {
            throw new Exception('Entity has no primary key');
        }

        if (1 === \count($ids)) {
            $this->id = reset($ids);
        } else {
            $objectID = '';
            foreach ($ids as $key => $value) {
                $objectID .= $key.'-'.$value.'__';
            }

            $this->id = rtrim($objectID, '_');
        }
    }

    /**
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }
}
