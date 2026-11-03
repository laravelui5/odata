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
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new ChartDefinitionType(
            title: $this->title,
            description: $this->description,
            chartType: $this->chartType,
            axisScaling: $this->axisScaling,
            measures: $this->measures,
            dynamicMeasures: $this->dynamicMeasures,
            measureAttributes: $this->measureAttributes,
            dimensions: $this->dimensions,
            dimensionAttributes: $this->dimensionAttributes,
            actions: $this->actions,
        ))->toRecord();
    }
}
