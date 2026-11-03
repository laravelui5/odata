<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class SemanticObjectMappingConstant extends SemanticObjectMappingAbstract
{
    public const string TYPE = 'com.sap.vocabularies.Common.v1.SemanticObjectMappingConstant';

    public const array FIELDS = [
        'SemanticObjectProperty' => ['semanticObjectProperty', 'Edm.String', true],
        'Constant' => ['constant', 'Edm.PrimitiveType', true],
    ];

    /**
     * @param string|Path|null $semanticObjectProperty Name of the Semantic Object property
     * @param mixed $constant Constant value for the Semantic Object property
     */
    public function __construct(
        string|Path|null $semanticObjectProperty = null,
        public mixed $constant = null,
    ) {
        parent::__construct(
            semanticObjectProperty: $semanticObjectProperty,
        );
    }
}
