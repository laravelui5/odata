<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Aggregation\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

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

    public function __construct(
        public readonly array $transformations,
        public readonly array $customAggregationMethods,
        public readonly mixed $rollup,
        public readonly bool $from,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Aggregation.ApplySupportedBase',
            new PropertyValue('Transformations', new ConstantAnnotationValue('String', (string) $this->transformations)),
            new PropertyValue('CustomAggregationMethods', new ConstantAnnotationValue('String', (string) $this->customAggregationMethods)),
            new PropertyValue('Rollup', new ConstantAnnotationValue('String', (string) $this->rollup)),
            new PropertyValue('From', new ConstantAnnotationValue('Boolean', (string) $this->from)),
        );
    }
}
