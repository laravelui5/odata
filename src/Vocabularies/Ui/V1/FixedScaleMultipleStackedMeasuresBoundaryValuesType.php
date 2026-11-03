<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class FixedScaleMultipleStackedMeasuresBoundaryValuesType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.FixedScaleMultipleStackedMeasuresBoundaryValuesType';

    public const array FIELDS = [
        'MinimumValue' => ['minimumValue', 'Edm.Decimal', true],
        'MaximumValue' => ['maximumValue', 'Edm.Decimal', true],
    ];

    public function __construct(
        public float|Path|null $minimumValue = null,
        public float|Path|null $maximumValue = null,
    ) {}
}
