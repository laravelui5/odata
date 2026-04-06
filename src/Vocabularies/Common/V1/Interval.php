<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Type\ComplexTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * An interval with lower and upper boundaries described by two properties
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Interval implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Common.v1.Interval';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
        ComplexTypeInterface::class,
    ];

    public function __construct(
        public readonly string $lowerBoundary,
        public readonly bool $lowerBoundaryIncluded,
        public readonly string $upperBoundary,
        public readonly bool $upperBoundaryIncluded,
        public readonly ?string $label = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new RecordAnnotationValue(
            'Common.IntervalType',
            new PropertyValue('Label', new ConstantAnnotationValue('String', (string) $this->label)),
            new PropertyValue('LowerBoundary', new ConstantAnnotationValue('PropertyPath', (string) $this->lowerBoundary)),
            new PropertyValue('LowerBoundaryIncluded', new ConstantAnnotationValue('Boolean', (string) $this->lowerBoundaryIncluded)),
            new PropertyValue('UpperBoundary', new ConstantAnnotationValue('PropertyPath', (string) $this->upperBoundary)),
            new PropertyValue('UpperBoundaryIncluded', new ConstantAnnotationValue('Boolean', (string) $this->upperBoundaryIncluded)),
        );
    }
}
