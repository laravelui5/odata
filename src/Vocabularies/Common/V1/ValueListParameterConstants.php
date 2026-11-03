<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ValueListParameterConstants extends ValueListParameter
{
    public const string TYPE = 'com.sap.vocabularies.Common.v1.ValueListParameterConstants';

    public const array FIELDS = [
        'ValueListProperty' => ['valueListProperty', 'Edm.String', true],
        'Constants' => ['constants', 'Collection(Edm.PrimitiveType)', false],
    ];

    /**
     * @param string|Path|null $valueListProperty Path to property in the value list . Format is identical to PropertyPath annotations.
     * @param list<mixed>|Path|null $constants List of constant values that are used to filter the value list with `eq` comparisons connected by `or` operators, using the same representation as property default values, see [CSDL XML, 7.2.7 Default Value](https://docs.oasis-open.org/odata/odata-csdl-xml/v4.01/odata-csdl-xml-v4.01.html#sec_DefaultValue). Initial values are significant.
     */
    public function __construct(
        string|Path|null $valueListProperty = null,
        public array|Path|null $constants = null,
    ) {
        parent::__construct(
            valueListProperty: $valueListProperty,
        );
    }
}
