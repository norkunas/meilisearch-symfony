<?php

declare(strict_types=1);

namespace Meilisearch\Bundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

final class MeilisearchBundle extends Bundle
{
    public const VERSION = '0.15.8';

    public static function qualifiedVersion(): string
    {
        return \sprintf('Meilisearch Symfony (v%s)', self::VERSION);
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
