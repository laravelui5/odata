<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

/**
 * Maps a property of the Semantic Object to a property of the annotated entity type or a sibling property of the annotated property or a constant value
 */
final readonly class SemanticObjectMappingAbstract
{
    public function __construct(
        public readonly string $semanticObjectProperty,
    ) {}
}
