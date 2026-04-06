<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use Attribute;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * The annotated navigation property represents a logical composition, even though it is non-containment
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Composition implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Common.v1.Composition';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        NavigationPropertyInterface::class,
    ];

    public function __construct(
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return null;
    }
}
