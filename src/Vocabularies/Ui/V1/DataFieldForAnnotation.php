<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * A structured piece of data described by an annotation
 */
final readonly class DataFieldForAnnotation
{
    public function __construct(
        public readonly string $target,
    ) {}
}
