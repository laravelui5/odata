<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Type\ComplexTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;

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

    /**
     * @param string|Path|null $label A short, human-readable text suitable for labels and captions in UIs
     * @param string|Path|null $lowerBoundary Property holding the lower interval boundary
     * @param bool|Path|null $lowerBoundaryIncluded The lower boundary value is included in the interval
     * @param string|Path|null $upperBoundary Property holding the upper interval boundary
     * @param bool|Path|null $upperBoundaryIncluded The upper boundary value is included in the interval
     */
    public function __construct(
        public string|Path|null $label = null,
        public string|Path|null $lowerBoundary = null,
        public bool|Path|null $lowerBoundaryIncluded = null,
        public string|Path|null $upperBoundary = null,
        public bool|Path|null $upperBoundaryIncluded = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): AnnotationValueInterface
    {
        return (new IntervalType(
            label: $this->label,
            lowerBoundary: $this->lowerBoundary,
            lowerBoundaryIncluded: $this->lowerBoundaryIncluded,
            upperBoundary: $this->upperBoundary,
            upperBoundaryIncluded: $this->upperBoundaryIncluded,
        ))->toRecord();
    }
}
