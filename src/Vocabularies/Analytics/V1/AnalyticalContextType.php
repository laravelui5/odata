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

    public function __construct(
        public string|Path|null $property = null,
        public string|Path|null $dynamicProperty = null,
        public bool|Path|null $dimension = null,
        public bool|Path|null $measure = null,
        public bool|Path|null $accumulativeMeasure = null,
    ) {}
}
