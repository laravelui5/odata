<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Thresholds for an aggregation level
 */
final readonly class LevelThresholdsType extends CriticalityThresholdsType
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.LevelThresholdsType';

    public const array FIELDS = [
        'AcceptanceRangeLowValue' => ['acceptanceRangeLowValue', 'Edm.PrimitiveType', false],
        'AcceptanceRangeHighValue' => ['acceptanceRangeHighValue', 'Edm.PrimitiveType', false],
        'ToleranceRangeLowValue' => ['toleranceRangeLowValue', 'Edm.PrimitiveType', false],
        'ToleranceRangeHighValue' => ['toleranceRangeHighValue', 'Edm.PrimitiveType', false],
        'DeviationRangeLowValue' => ['deviationRangeLowValue', 'Edm.PrimitiveType', false],
        'DeviationRangeHighValue' => ['deviationRangeHighValue', 'Edm.PrimitiveType', false],
        'AggregationLevel' => ['aggregationLevel', 'Collection(Edm.PropertyPath)', false],
    ];

    /**
     * @param mixed $acceptanceRangeLowValue Lowest value that is considered positive
     * @param mixed $acceptanceRangeHighValue Highest value that is considered positive
     * @param mixed $toleranceRangeLowValue Lowest value that is considered neutral
     * @param mixed $toleranceRangeHighValue Highest value that is considered neutral
     * @param mixed $deviationRangeLowValue Lowest value that is considered critical
     * @param mixed $deviationRangeHighValue Highest value that is considered critical
     * @param list<string>|Path|null $aggregationLevel An unordered tuple of dimensions, i.e. properties which are intended to be used for grouping in aggregating requests. In analytical UIs, e.g. an analytical chart, the aggregation level typically corresponds to the visible dimensions.
     */
    public function __construct(
        mixed $acceptanceRangeLowValue = null,
        mixed $acceptanceRangeHighValue = null,
        mixed $toleranceRangeLowValue = null,
        mixed $toleranceRangeHighValue = null,
        mixed $deviationRangeLowValue = null,
        mixed $deviationRangeHighValue = null,
        public array|Path|null $aggregationLevel = null,
    ) {
        parent::__construct(
            acceptanceRangeLowValue: $acceptanceRangeLowValue,
            acceptanceRangeHighValue: $acceptanceRangeHighValue,
            toleranceRangeLowValue: $toleranceRangeLowValue,
            toleranceRangeHighValue: $toleranceRangeHighValue,
            deviationRangeLowValue: $deviationRangeLowValue,
            deviationRangeHighValue: $deviationRangeHighValue,
        );
    }
}
