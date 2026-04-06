<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Aggregation\V1;

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
 * Defines a recursive hierarchy (OData-Data-Agg-v4.0, section 5.5.2)
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class RecursiveHierarchy implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Aggregation.V1.RecursiveHierarchy';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
    ];

    public function __construct(
        public readonly string $nodeProperty,
        public readonly string $parentNavigationProperty,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Aggregation.RecursiveHierarchyType',
            new PropertyValue('NodeProperty', new ConstantAnnotationValue('PropertyPath', (string) $this->nodeProperty)),
            new PropertyValue('ParentNavigationProperty', new ConstantAnnotationValue('NavigationPropertyPath', (string) $this->parentNavigationProperty)),
        );
    }
}
