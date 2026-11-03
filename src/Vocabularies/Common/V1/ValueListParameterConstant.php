<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ValueListParameterConstant extends ValueListParameter
{
    public const string TYPE = 'com.sap.vocabularies.Common.v1.ValueListParameterConstant';

    public const array FIELDS = [
        'ValueListProperty' => ['valueListProperty', 'Edm.String', true],
        'Constant' => ['constant', 'Edm.PrimitiveType', true],
        'InitialValueIsSignificant' => ['initialValueIsSignificant', 'Edm.Boolean', false],
    ];

    /**
     * @param string|Path|null $valueListProperty Path to property in the value list . Format is identical to PropertyPath annotations.
     * @param mixed $constant Constant value that is used to filter the value list with `eq` comparison, using the same representation as property default values, see [CSDL XML, 7.2.7 Default Value](https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_DefaultValue)
     * @param bool|Path|null $initialValueIsSignificant Initial value, e.g. empty string, is a valid and significant value
     */
    public function __construct(
        string|Path|null $valueListProperty = null,
        public mixed $constant = null,
        public bool|Path|null $initialValueIsSignificant = null,
    ) {
        parent::__construct(
            valueListProperty: $valueListProperty,
        );
    }
}
