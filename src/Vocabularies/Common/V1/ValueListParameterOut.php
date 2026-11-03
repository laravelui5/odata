<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ValueListParameterOut extends ValueListParameter
{
    public const string TYPE = 'com.sap.vocabularies.Common.v1.ValueListParameterOut';

    public const array FIELDS = [
        'ValueListProperty' => ['valueListProperty', 'Edm.String', true],
        'LocalDataProperty' => ['localDataProperty', 'Edm.PropertyPath', true],
    ];

    /**
     * @param string|Path|null $valueListProperty Path to property in the value list . Format is identical to PropertyPath annotations.
     * @param string|Path|null $localDataProperty Path to property that is filled from response
     */
    public function __construct(
        string|Path|null $valueListProperty = null,
        public string|Path|null $localDataProperty = null,
    ) {
        parent::__construct(
            valueListProperty: $valueListProperty,
        );
    }
}
