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
 * Visualization of multiple data points
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Chart implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.UI.v1.Chart';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
    ];

    public function __construct(
        public readonly mixed $chartType,
        public readonly array $measures,
        public readonly array $dynamicMeasures,
        public readonly array $measureAttributes,
        public readonly array $dimensions,
        public readonly array $dimensionAttributes,
        public readonly array $actions,
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly mixed $axisScaling = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'UI.ChartDefinitionType',
            new PropertyValue('Title', new ConstantAnnotationValue('String', (string) $this->title)),
            new PropertyValue('Description', new ConstantAnnotationValue('String', (string) $this->description)),
            new PropertyValue('ChartType', new ConstantAnnotationValue('String', (string) $this->chartType)),
            new PropertyValue('AxisScaling', new ConstantAnnotationValue('String', (string) $this->axisScaling)),
            new PropertyValue('Measures', new ConstantAnnotationValue('String', (string) $this->measures)),
            new PropertyValue('DynamicMeasures', new ConstantAnnotationValue('String', (string) $this->dynamicMeasures)),
            new PropertyValue('MeasureAttributes', new ConstantAnnotationValue('String', (string) $this->measureAttributes)),
            new PropertyValue('Dimensions', new ConstantAnnotationValue('String', (string) $this->dimensions)),
            new PropertyValue('DimensionAttributes', new ConstantAnnotationValue('String', (string) $this->dimensionAttributes)),
            new PropertyValue('Actions', new ConstantAnnotationValue('String', (string) $this->actions)),
        );
    }
}
