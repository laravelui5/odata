<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class FilterExpressionRestrictionType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Common.v1.FilterExpressionRestrictionType';

    public const array FIELDS = [
        'Property' => ['property', 'Edm.PropertyPath', false],
        'AllowedExpressions' => ['allowedExpressions', 'com.sap.vocabularies.Common.v1.FilterExpressionType', false],
    ];

    public function __construct(
        public string|Path|null $property = null,
        public FilterExpressionType|Path|null $allowedExpressions = null,
    ) {}
}
