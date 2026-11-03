<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Maps a property of the Semantic Object to a property of the annotated entity type or a sibling property of the annotated property or a constant value
 */
abstract readonly class SemanticObjectMappingAbstract extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Common.v1.SemanticObjectMappingAbstract';

    public const array FIELDS = [
        'SemanticObjectProperty' => ['semanticObjectProperty', 'Edm.String', true],
    ];

    public function __construct(
        public string|Path|null $semanticObjectProperty = null,
    ) {}
}
