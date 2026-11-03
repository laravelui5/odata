<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Single-valued parameter
 */
final readonly class Parameter extends ParameterAbstract
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.Parameter';

    public const array FIELDS = [
        'PropertyName' => ['propertyName', 'Edm.PropertyPath', true],
        'PropertyValue' => ['propertyValue', 'Edm.PrimitiveType', true],
    ];

    public function __construct(
        public string|Path|null $propertyName = null,
        public mixed $propertyValue = null,
    ) {}
}
