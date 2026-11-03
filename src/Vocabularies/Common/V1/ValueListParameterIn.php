<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ValueListParameterIn extends ValueListParameter
{
    public const string TYPE = 'com.sap.vocabularies.Common.v1.ValueListParameterIn';

    public const array FIELDS = [
        'ValueListProperty' => ['valueListProperty', 'Edm.String', true],
        'LocalDataProperty' => ['localDataProperty', 'Edm.PropertyPath', true],
        'InitialValueIsSignificant' => ['initialValueIsSignificant', 'Edm.Boolean', false],
    ];

    /**
     * @param string|Path|null $valueListProperty Path to property in the value list . Format is identical to PropertyPath annotations.
     * @param string|Path|null $localDataProperty Path to property that is used to filter the value list with `eq` comparison
     * @param bool|Path|null $initialValueIsSignificant Initial value, e.g. empty string, is a valid and significant value
     */
    public function __construct(
        string|Path|null $valueListProperty = null,
        public string|Path|null $localDataProperty = null,
        public bool|Path|null $initialValueIsSignificant = null,
    ) {
        parent::__construct(
            valueListProperty: $valueListProperty,
        );
    }
}
