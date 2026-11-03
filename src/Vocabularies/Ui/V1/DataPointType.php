<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Vocabularies\Communication\V1\ContactType;

final readonly class DataPointType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.DataPointType';

    public const array FIELDS = [
        'Title' => ['title', 'Edm.String', false],
        'Description' => ['description', 'Edm.String', false],
        'LongDescription' => ['longDescription', 'Edm.String', false],
        'Value' => ['value', 'Edm.PrimitiveType', true],
        'TargetValue' => ['targetValue', 'Edm.PrimitiveType', false],
        'ForecastValue' => ['forecastValue', 'Edm.PrimitiveType', false],
        'MinimumValue' => ['minimumValue', 'Edm.Decimal', false],
        'MaximumValue' => ['maximumValue', 'Edm.Decimal', false],
        'ValueFormat' => ['valueFormat', 'com.sap.vocabularies.UI.v1.NumberFormat', false],
        'Visualization' => ['visualization', 'com.sap.vocabularies.UI.v1.VisualizationType', false],
        'SampleSize' => ['sampleSize', 'Edm.PrimitiveType', false],
        'ReferencePeriod' => ['referencePeriod', 'com.sap.vocabularies.UI.v1.ReferencePeriod', false],
        'Criticality' => ['criticality', 'com.sap.vocabularies.UI.v1.CriticalityType', false],
        'CriticalityLabels' => ['criticalityLabels', 'Edm.AnnotationPath', false],
        'CriticalityRepresentation' => ['criticalityRepresentation', 'com.sap.vocabularies.UI.v1.CriticalityRepresentationType', false],
        'CriticalityCalculation' => ['criticalityCalculation', 'com.sap.vocabularies.UI.v1.CriticalityCalculationType', false],
        'Trend' => ['trend', 'com.sap.vocabularies.UI.v1.TrendType', false],
        'TrendCalculation' => ['trendCalculation', 'com.sap.vocabularies.UI.v1.TrendCalculationType', false],
        'Responsible' => ['responsible', 'com.sap.vocabularies.Communication.v1.ContactType', false],
    ];

    /**
     * @param string|Path|null $title Title of the data point
     * @param string|Path|null $description Short description
     * @param string|Path|null $longDescription Full description
     * @param mixed $value Numeric value
     * @param mixed $targetValue Target value
     * @param mixed $forecastValue Forecast value
     * @param float|Path|null $minimumValue Minimum value (for output rendering)
     * @param float|Path|null $maximumValue Maximum value (for output rendering)
     * @param NumberFormat|Path|null $valueFormat Number format
     * @param VisualizationType|Path|null $visualization Preferred visualization
     * @param mixed $sampleSize Sample size used for the determination of the data point; should contain just integer value as Edm.Byte, Edm.SByte, Edm.Intxx, and Edm.Decimal with scale 0.
     * @param ReferencePeriod|Path|null $referencePeriod Reference period
     * @param CriticalityType|Path|null $criticality Service-calculated criticality, alternative to CriticalityCalculation
     * @param string|Path|null $criticalityLabels Custom labels for the criticality legend. Annotation path MUST end in UI.CriticalityLabels
     * @param CriticalityRepresentationType|Path|null $criticalityRepresentation Decides if criticality is visualized in addition by means of an icon
     * @param CriticalityCalculationType|Path|null $criticalityCalculation Parameters for client-calculated criticality, alternative to Criticality
     * @param TrendType|Path|null $trend Service-calculated trend, alternative to TrendCalculation
     * @param TrendCalculationType|Path|null $trendCalculation Parameters for client-calculated trend, alternative to Trend
     * @param ContactType|Path|null $responsible Contact person
     */
    public function __construct(
        public string|Path|null $title = null,
        public string|Path|null $description = null,
        public string|Path|null $longDescription = null,
        public mixed $value = null,
        public mixed $targetValue = null,
        public mixed $forecastValue = null,
        public float|Path|null $minimumValue = null,
        public float|Path|null $maximumValue = null,
        public NumberFormat|Path|null $valueFormat = null,
        public VisualizationType|Path|null $visualization = null,
        public mixed $sampleSize = null,
        public ReferencePeriod|Path|null $referencePeriod = null,
        public CriticalityType|Path|null $criticality = null,
        public string|Path|null $criticalityLabels = null,
        public CriticalityRepresentationType|Path|null $criticalityRepresentation = null,
        public CriticalityCalculationType|Path|null $criticalityCalculation = null,
        public TrendType|Path|null $trend = null,
        public TrendCalculationType|Path|null $trendCalculation = null,
        public ContactType|Path|null $responsible = null,
    ) {}
}
