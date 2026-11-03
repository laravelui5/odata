<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Vocabularies\Communication\V1\ContactType;

/**
 * Visualization of a single point of data, typically a number; may also be textual, e.g. a status value
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class DataPoint implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.UI.v1.DataPoint';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
    ];

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
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new DataPointType(
            title: $this->title,
            description: $this->description,
            longDescription: $this->longDescription,
            value: $this->value,
            targetValue: $this->targetValue,
            forecastValue: $this->forecastValue,
            minimumValue: $this->minimumValue,
            maximumValue: $this->maximumValue,
            valueFormat: $this->valueFormat,
            visualization: $this->visualization,
            sampleSize: $this->sampleSize,
            referencePeriod: $this->referencePeriod,
            criticality: $this->criticality,
            criticalityLabels: $this->criticalityLabels,
            criticalityRepresentation: $this->criticalityRepresentation,
            criticalityCalculation: $this->criticalityCalculation,
            trend: $this->trend,
            trendCalculation: $this->trendCalculation,
            responsible: $this->responsible,
        ))->toRecord();
    }
}
