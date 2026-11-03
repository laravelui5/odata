<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Aggregation\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface;

/**
 * Default support of the `$apply` system query option for all collection-valued resources in the container
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class ApplySupportedDefaults implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Aggregation.V1.ApplySupportedDefaults';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityContainerInterface::class,
    ];

    /**
     * @param list<string>|Path|null $transformations Transformations that can be used in `$apply`
     * @param list<string>|Path|null $customAggregationMethods Qualified names of custom aggregation methods that can be used in `aggregate(...with...)`
     * @param RollupType|Path|null $rollup The service supports rollup hierarchies in a `groupby` transformation
     * @param bool|Path|null $from The service supports the `from` keyword in an `aggregate` transformation
     */
    public function __construct(
        public array|Path|null $transformations = null,
        public array|Path|null $customAggregationMethods = null,
        public RollupType|Path|null $rollup = null,
        public bool|Path|null $from = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): AnnotationValueInterface
    {
        return (new ApplySupportedBase(
            transformations: $this->transformations,
            customAggregationMethods: $this->customAggregationMethods,
            rollup: $this->rollup,
            from: $this->from,
        ))->toRecord();
    }
}
