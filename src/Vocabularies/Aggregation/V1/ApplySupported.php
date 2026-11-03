<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Aggregation\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;

/**
 * This entity set or collection supports the `$apply` system query option
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final readonly class ApplySupported implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Aggregation.V1.ApplySupported';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
        PropertyInterface::class,
        NavigationPropertyInterface::class,
        EntityTypeInterface::class,
    ];

    /**
     * @param list<string>|Path|null $transformations Transformations that can be used in `$apply`
     * @param list<string>|Path|null $customAggregationMethods Qualified names of custom aggregation methods that can be used in `aggregate(...with...)`
     * @param RollupType|Path|null $rollup The service supports rollup hierarchies in a `groupby` transformation
     * @param bool|Path|null $from The service supports the `from` keyword in an `aggregate` transformation
     * @param bool|Path|null $propertyRestrictions Only properties marked as `Groupable` can be used in the `groupby` transformation, and only those marked as `Aggregatable` can be used in the `aggregate` transformation
     * @param list<mixed>|Path|null $groupableProperties A non-empty collection indicates that only the listed properties of the annotated target are supported by the `groupby` transformation
     * @param list<AggregatablePropertyType>|Path|null $aggregatableProperties A non-empty collection indicates that only the listed properties of the annotated target can be used in the `aggregate` transformation, optionally restricted to the specified aggregation methods
     */
    public function __construct(
        public array|Path|null $transformations = null,
        public array|Path|null $customAggregationMethods = null,
        public RollupType|Path|null $rollup = null,
        public bool|Path|null $from = null,
        public bool|Path|null $propertyRestrictions = null,
        public array|Path|null $groupableProperties = null,
        public array|Path|null $aggregatableProperties = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new ApplySupportedType(
            transformations: $this->transformations,
            customAggregationMethods: $this->customAggregationMethods,
            rollup: $this->rollup,
            from: $this->from,
            propertyRestrictions: $this->propertyRestrictions,
            groupableProperties: $this->groupableProperties,
            aggregatableProperties: $this->aggregatableProperties,
        ))->toRecord();
    }
}
