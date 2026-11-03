<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Exactly one of `Measure` and `DynamicMeasure` must be present
 */
final readonly class ChartMeasureAttributeType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.ChartMeasureAttributeType';

    public const array FIELDS = [
        'Measure' => ['measure', 'Edm.PropertyPath', false],
        'DynamicMeasure' => ['dynamicMeasure', 'Edm.AnnotationPath', false],
        'Role' => ['role', 'com.sap.vocabularies.UI.v1.ChartMeasureRoleType', false],
        'DataPoint' => ['dataPoint', 'Edm.AnnotationPath', false],
        'UseSequentialColorLevels' => ['useSequentialColorLevels', 'Edm.Boolean', false],
    ];

    /**
     * @param string|Path|null $dynamicMeasure Dynamic property introduced by an annotation and used as a measure in a chart
     * @param string|Path|null $dataPoint Annotation path MUST end in @UI.DataPoint and the data point's Value MUST be the same property as in Measure
     * @param bool|Path|null $useSequentialColorLevels All measures for which this setting is true should be assigned to levels of the same color.
     */
    public function __construct(
        public string|Path|null $measure = null,
        public string|Path|null $dynamicMeasure = null,
        public ChartMeasureRoleType|Path|null $role = null,
        public string|Path|null $dataPoint = null,
        public bool|Path|null $useSequentialColorLevels = null,
    ) {}
}
