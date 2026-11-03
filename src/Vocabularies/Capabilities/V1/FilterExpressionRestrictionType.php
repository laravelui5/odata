<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class FilterExpressionRestrictionType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.FilterExpressionRestrictionType';

    public const array FIELDS = [
        'Property' => ['property', 'Edm.PropertyPath', false],
        'AllowedExpressions' => ['allowedExpressions', 'Edm.String', false],
    ];

    public function __construct(
        public string|Path|null $property = null,
        public string|Path|null $allowedExpressions = null,
    ) {}
}
