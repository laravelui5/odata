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
     * @param string|Path|null $title Title of the chart
     * @param string|Path|null $description Short description
     * @param ChartType|Path|null $chartType Chart type
     * @param ChartAxisScalingType|Path|null $axisScaling Describes the scale of the chart value axes
     * @param list<string>|Path|null $measures Measures of the chart, e.g. size and color in a bubble chart
     * @param list<string>|Path|null $dynamicMeasures Dynamic properties introduced by annotations and used as measures of the chart
     * @param list<ChartMeasureAttributeType>|Path|null $measureAttributes Describes Attributes for Measures. All Measures used in this collection must also be part of the Measures Property.
     * @param list<string>|Path|null $dimensions Dimensions of the chart, e.g. x- and y-axis of a bubble chart
     * @param list<ChartDimensionAttributeType>|Path|null $dimensionAttributes Describes Attributes for Dimensions. All Dimensions used in this collection must also be part of the Dimensions Property.
     * @param list<DataFieldAbstract>|Path|null $actions Available actions and action groups
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
