<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class SemanticObjectMappingType extends SemanticObjectMappingAbstract
{
    public const string TYPE = 'com.sap.vocabularies.Common.v1.SemanticObjectMappingType';

    public const array FIELDS = [
        'SemanticObjectProperty' => ['semanticObjectProperty', 'Edm.String', true],
        'LocalProperty' => ['localProperty', 'Edm.PropertyPath', true],
    ];

    public function __construct(
        string|Path|null $semanticObjectProperty = null,
        public string|Path|null $localProperty = null,
    ) {
        parent::__construct(
            semanticObjectProperty: $semanticObjectProperty,
        );
    }
}
