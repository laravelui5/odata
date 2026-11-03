<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ChartDefinitionType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.ChartDefinitionType';

    public const array FIELDS = [
        'Title' => ['title', 'Edm.String', false],
        'Description' => ['description', 'Edm.String', false],
        'ChartType' => ['chartType', 'com.sap.vocabularies.UI.v1.ChartType', true],
        'AxisScaling' => ['axisScaling', 'com.sap.vocabularies.UI.v1.ChartAxisScalingType', false],
        'Measures' => ['measures', 'Collection(Edm.PropertyPath)', false],
        'DynamicMeasures' => ['dynamicMeasures', 'Collection(Edm.AnnotationPath)', false],
        'MeasureAttributes' => ['measureAttributes', 'Collection(com.sap.vocabularies.UI.v1.ChartMeasureAttributeType)', false],
        'Dimensions' => ['dimensions', 'Collection(Edm.PropertyPath)', false],
        'DimensionAttributes' => ['dimensionAttributes', 'Collection(com.sap.vocabularies.UI.v1.ChartDimensionAttributeType)', false],
        'Actions' => ['actions', 'Collection(com.sap.vocabularies.UI.v1.DataFieldAbstract)', false],
    ];

    /**
     * @param list<string>|Path|null $measures
     * @param list<string>|Path|null $dynamicMeasures
     * @param list<ChartMeasureAttributeType>|Path|null $measureAttributes
     * @param list<string>|Path|null $dimensions
     * @param list<ChartDimensionAttributeType>|Path|null $dimensionAttributes
     * @param list<DataFieldAbstract>|Path|null $actions
     */
    public function __construct(
        public string|Path|null $title = null,
        public string|Path|null $description = null,
        public ChartType|Path|null $chartType = null,
        public ChartAxisScalingType|Path|null $axisScaling = null,
        public array|Path|null $measures = null,
        public array|Path|null $dynamicMeasures = null,
        public array|Path|null $measureAttributes = null,
        public array|Path|null $dimensions = null,
        public array|Path|null $dimensionAttributes = null,
        public array|Path|null $actions = null,
    ) {}
}
