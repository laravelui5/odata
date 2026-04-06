<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use Attribute;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * Property encodes a calendar month number as string following the logical pattern MM consisting of two digits.
            The string matches the regex pattern 0[1-9]|1[0-2]
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class IsCalendarMonth implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Common.v1.IsCalendarMonth';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        PropertyInterface::class,
    ];

    public function __construct(
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return null;
    }
}
