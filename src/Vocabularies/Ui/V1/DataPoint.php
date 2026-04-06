<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

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
        public readonly mixed $value,
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly ?string $longDescription = null,
        public readonly mixed $targetValue = null,
        public readonly mixed $forecastValue = null,
        public readonly ?float $minimumValue = null,
        public readonly ?float $maximumValue = null,
        public readonly mixed $valueFormat = null,
        public readonly mixed $visualization = null,
        public readonly mixed $sampleSize = null,
        public readonly mixed $referencePeriod = null,
        public readonly mixed $criticality = null,
        public readonly ?string $criticalityLabels = null,
        public readonly mixed $criticalityRepresentation = null,
        public readonly mixed $criticalityCalculation = null,
        public readonly mixed $trend = null,
        public readonly mixed $trendCalculation = null,
        public readonly mixed $responsible = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'UI.DataPointType',
            new PropertyValue('Title', new ConstantAnnotationValue('String', (string) $this->title)),
            new PropertyValue('Description', new ConstantAnnotationValue('String', (string) $this->description)),
            new PropertyValue('LongDescription', new ConstantAnnotationValue('String', (string) $this->longDescription)),
            new PropertyValue('Value', new ConstantAnnotationValue('String', (string) $this->value)),
            new PropertyValue('TargetValue', new ConstantAnnotationValue('String', (string) $this->targetValue)),
            new PropertyValue('ForecastValue', new ConstantAnnotationValue('String', (string) $this->forecastValue)),
            new PropertyValue('MinimumValue', new ConstantAnnotationValue('Decimal', (string) $this->minimumValue)),
            new PropertyValue('MaximumValue', new ConstantAnnotationValue('Decimal', (string) $this->maximumValue)),
            new PropertyValue('ValueFormat', new ConstantAnnotationValue('String', (string) $this->valueFormat)),
            new PropertyValue('Visualization', new ConstantAnnotationValue('String', (string) $this->visualization)),
            new PropertyValue('SampleSize', new ConstantAnnotationValue('String', (string) $this->sampleSize)),
            new PropertyValue('ReferencePeriod', new ConstantAnnotationValue('String', (string) $this->referencePeriod)),
            new PropertyValue('Criticality', new ConstantAnnotationValue('String', (string) $this->criticality)),
            new PropertyValue('CriticalityLabels', new ConstantAnnotationValue('AnnotationPath', (string) $this->criticalityLabels)),
            new PropertyValue('CriticalityRepresentation', new ConstantAnnotationValue('String', (string) $this->criticalityRepresentation)),
            new PropertyValue('CriticalityCalculation', new ConstantAnnotationValue('String', (string) $this->criticalityCalculation)),
            new PropertyValue('Trend', new ConstantAnnotationValue('String', (string) $this->trend)),
            new PropertyValue('TrendCalculation', new ConstantAnnotationValue('String', (string) $this->trendCalculation)),
            new PropertyValue('Responsible', new ConstantAnnotationValue('String', (string) $this->responsible)),
        );
    }
}
