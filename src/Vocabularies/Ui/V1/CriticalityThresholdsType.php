<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Thresholds for calculating the criticality of a value
 */
readonly class CriticalityThresholdsType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.CriticalityThresholdsType';

    public const array FIELDS = [
        'AcceptanceRangeLowValue' => ['acceptanceRangeLowValue', 'Edm.PrimitiveType', false],
        'AcceptanceRangeHighValue' => ['acceptanceRangeHighValue', 'Edm.PrimitiveType', false],
        'ToleranceRangeLowValue' => ['toleranceRangeLowValue', 'Edm.PrimitiveType', false],
        'ToleranceRangeHighValue' => ['toleranceRangeHighValue', 'Edm.PrimitiveType', false],
        'DeviationRangeLowValue' => ['deviationRangeLowValue', 'Edm.PrimitiveType', false],
        'DeviationRangeHighValue' => ['deviationRangeHighValue', 'Edm.PrimitiveType', false],
    ];

    /**
     * @param mixed $acceptanceRangeLowValue Lowest value that is considered positive
     * @param mixed $acceptanceRangeHighValue Highest value that is considered positive
     * @param mixed $toleranceRangeLowValue Lowest value that is considered neutral
     * @param mixed $toleranceRangeHighValue Highest value that is considered neutral
     * @param mixed $deviationRangeLowValue Lowest value that is considered critical
     * @param mixed $deviationRangeHighValue Highest value that is considered critical
     */
    public function __construct(
        public mixed $acceptanceRangeLowValue = null,
        public mixed $acceptanceRangeHighValue = null,
        public mixed $toleranceRangeLowValue = null,
        public mixed $toleranceRangeHighValue = null,
        public mixed $deviationRangeLowValue = null,
        public mixed $deviationRangeHighValue = null,
    ) {}
}
