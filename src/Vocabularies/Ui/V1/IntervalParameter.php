<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Interval parameter formed with a 'from' and a 'to' property
 */
final readonly class IntervalParameter extends ParameterAbstract
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.IntervalParameter';

    public const array FIELDS = [
        'PropertyNameFrom' => ['propertyNameFrom', 'Edm.PropertyPath', true],
        'PropertyValueFrom' => ['propertyValueFrom', 'Edm.PrimitiveType', true],
        'PropertyNameTo' => ['propertyNameTo', 'Edm.PropertyPath', true],
        'PropertyValueTo' => ['propertyValueTo', 'Edm.PrimitiveType', true],
    ];

    public function __construct(
        public string|Path|null $propertyNameFrom = null,
        public mixed $propertyValueFrom = null,
        public string|Path|null $propertyNameTo = null,
        public mixed $propertyValueTo = null,
    ) {}
}
