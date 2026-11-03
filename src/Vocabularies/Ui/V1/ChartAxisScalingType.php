<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ChartAxisScalingType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.ChartAxisScalingType';

    public const array FIELDS = [
        'ScaleBehavior' => ['scaleBehavior', 'com.sap.vocabularies.UI.v1.ChartAxisScaleBehaviorType', false],
        'AutoScaleBehavior' => ['autoScaleBehavior', 'com.sap.vocabularies.UI.v1.ChartAxisAutoScaleBehaviorType', false],
        'FixedScaleMultipleStackedMeasuresBoundaryValues' => ['fixedScaleMultipleStackedMeasuresBoundaryValues', 'com.sap.vocabularies.UI.v1.FixedScaleMultipleStackedMeasuresBoundaryValuesType', false],
    ];

    /**
     * @param ChartAxisScaleBehaviorType|Path|null $scaleBehavior Scale is fixed or adapts automatically to rendered values
     * @param ChartAxisAutoScaleBehaviorType|Path|null $autoScaleBehavior Settings for automatic scaling
     * @param FixedScaleMultipleStackedMeasuresBoundaryValuesType|Path|null $fixedScaleMultipleStackedMeasuresBoundaryValues Boundary values for fixed scaling of a stacking chart type with multiple measures
     */
    public function __construct(
        public ChartAxisScaleBehaviorType|Path|null $scaleBehavior = null,
        public ChartAxisAutoScaleBehaviorType|Path|null $autoScaleBehavior = null,
        public FixedScaleMultipleStackedMeasuresBoundaryValuesType|Path|null $fixedScaleMultipleStackedMeasuresBoundaryValues = null,
    ) {}
}
