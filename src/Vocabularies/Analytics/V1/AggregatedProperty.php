<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Analytics\V1;

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
 * Dynamic property for aggregate expression with specified aggregation method defined on the annotated entity type.
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AggregatedProperty implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Analytics.v1.AggregatedProperty';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
    ];

    public function __construct(
        public readonly string $name,
        public readonly string $aggregationMethod,
        public readonly string $aggregatableProperty,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Analytics.AggregatedPropertyType',
            new PropertyValue('Name', new ConstantAnnotationValue('String', (string) $this->name)),
            new PropertyValue('AggregationMethod', new ConstantAnnotationValue('String', (string) $this->aggregationMethod)),
            new PropertyValue('AggregatableProperty', new ConstantAnnotationValue('PropertyPath', (string) $this->aggregatableProperty)),
        );
    }
}
