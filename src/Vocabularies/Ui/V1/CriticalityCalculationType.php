<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Describes how to calculate the criticality of a value depending on the improvement direction
 */
final readonly class CriticalityCalculationType extends CriticalityThresholdsType
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.CriticalityCalculationType';

    public const array FIELDS = [
        'AcceptanceRangeLowValue' => ['acceptanceRangeLowValue', 'Edm.PrimitiveType', false],
        'AcceptanceRangeHighValue' => ['acceptanceRangeHighValue', 'Edm.PrimitiveType', false],
        'ToleranceRangeLowValue' => ['toleranceRangeLowValue', 'Edm.PrimitiveType', false],
        'ToleranceRangeHighValue' => ['toleranceRangeHighValue', 'Edm.PrimitiveType', false],
        'DeviationRangeLowValue' => ['deviationRangeLowValue', 'Edm.PrimitiveType', false],
        'DeviationRangeHighValue' => ['deviationRangeHighValue', 'Edm.PrimitiveType', false],
        'ReferenceValue' => ['referenceValue', 'Edm.PrimitiveType', false],
        'IsRelativeDifference' => ['isRelativeDifference', 'Edm.Boolean', false],
        'ImprovementDirection' => ['improvementDirection', 'com.sap.vocabularies.UI.v1.ImprovementDirectionType', true],
        'ConstantThresholds' => ['constantThresholds', 'Collection(com.sap.vocabularies.UI.v1.LevelThresholdsType)', false],
    ];

    /**
     * @param mixed $acceptanceRangeLowValue Lowest value that is considered positive
     * @param mixed $acceptanceRangeHighValue Highest value that is considered positive
     * @param mixed $toleranceRangeLowValue Lowest value that is considered neutral
     * @param mixed $toleranceRangeHighValue Highest value that is considered neutral
     * @param mixed $deviationRangeLowValue Lowest value that is considered critical
     * @param mixed $deviationRangeHighValue Highest value that is considered critical
     * @param mixed $referenceValue Reference value for the calculation, e.g. number of sales for the last year
     * @param bool|Path|null $isRelativeDifference Calculate with a relative difference
     * @param ImprovementDirectionType|Path|null $improvementDirection Describes in which direction the value improves
     * @param list<LevelThresholdsType>|Path|null $constantThresholds List of thresholds depending on the aggregation level as a set of constant values
     */
    public function __construct(
        mixed $acceptanceRangeLowValue = null,
        mixed $acceptanceRangeHighValue = null,
        mixed $toleranceRangeLowValue = null,
        mixed $toleranceRangeHighValue = null,
        mixed $deviationRangeLowValue = null,
        mixed $deviationRangeHighValue = null,
        public mixed $referenceValue = null,
        public bool|Path|null $isRelativeDifference = null,
        public ImprovementDirectionType|Path|null $improvementDirection = null,
        public array|Path|null $constantThresholds = null,
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
