<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Analytics\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Exactly one of `Property` and `DynamicProperty` must be present
 */
final readonly class AnalyticalContextType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Analytics.v1.AnalyticalContextType';

    public const array FIELDS = [
        'Property' => ['property', 'Edm.PropertyPath', false],
        'DynamicProperty' => ['dynamicProperty', 'Edm.AnnotationPath', false],
        'Dimension' => ['dimension', 'Edm.Boolean', false],
        'Measure' => ['measure', 'Edm.Boolean', false],
        'AccumulativeMeasure' => ['accumulativeMeasure', 'Edm.Boolean', false],
    ];

    /**
     * @param string|Path|null $property Property that is part of the analytical context
     * @param string|Path|null $dynamicProperty Dynamic property introduced by annotations that is part of the analytical context
     * @param bool|Path|null $dimension The property holds the key of a dimension
     * @param bool|Path|null $measure The property holds the numeric value of a measure
     * @param bool|Path|null $accumulativeMeasure The measure has non-negative and additive values; it can be used in whole-part charts, e.g. the Donut
     */
    public function __construct(
        public string|Path|null $property = null,
        public string|Path|null $dynamicProperty = null,
        public bool|Path|null $dimension = null,
        public bool|Path|null $measure = null,
        public bool|Path|null $accumulativeMeasure = null,
    ) {}
}
