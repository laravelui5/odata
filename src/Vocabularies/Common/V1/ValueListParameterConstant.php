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
