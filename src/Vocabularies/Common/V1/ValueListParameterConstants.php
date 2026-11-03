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
     * @param list<mixed>|Path|null $constants
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
