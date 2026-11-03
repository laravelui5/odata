<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Value range. If the range option only requires a single value, the value must be in the property Low
 */
final readonly class SelectionRangeType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.SelectionRangeType';

    public const array FIELDS = [
        'Sign' => ['sign', 'com.sap.vocabularies.UI.v1.SelectionRangeSignType', true],
        'Option' => ['option', 'com.sap.vocabularies.UI.v1.SelectionRangeOptionType', true],
        'Low' => ['low', 'Edm.PrimitiveType', true],
        'High' => ['high', 'Edm.PrimitiveType', false],
    ];

    public function __construct(
        public SelectionRangeSignType|Path|null $sign = null,
        public SelectionRangeOptionType|Path|null $option = null,
        public mixed $low = null,
        public mixed $high = null,
    ) {}
}
