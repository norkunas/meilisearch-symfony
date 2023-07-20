<?php

declare(strict_types=1);

namespace Meilisearch\Bundle;

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
     * @var non-empty-string
     */
    private string $primaryKey;

    private object $object;

    /**
     * @var \Stringable|string|int
     */
    private $id;

    private ?NormalizerInterface $normalizer;

    /**
     * @var array<mixed>
     */
    private array $normalizationContext;

    /**
     * @param non-empty-string       $indexUid
     * @param object                 $object
     * @param \Stringable|string|int $id
     * @param array<mixed>           $normalizationContext
     */
    public function __construct(
        string $indexUid,
        string $primaryKey,
        object $object,
        $id,
        NormalizerInterface $normalizer,
        array $normalizationContext = []
    ) {
        $this->indexUid = $indexUid;
        $this->primaryKey = $primaryKey;
        $this->object = $object;
        $this->id = $id;
        $this->normalizer = $normalizer;
        $this->normalizationContext = array_merge($normalizationContext, ['meilisearch' => true]);
    }

    /**
     * @return non-empty-string
     */
    public function getIndexUid(): string
    {
        return $this->indexUid;
    }

    /**
     * @return non-empty-string
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * @return \Stringable|string|int
     */
    public function getId()
    {
        return $this->id;
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
}
