<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\CollectionAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * [Referential constraints](https://oasis-tcs.github.io/odata-specs/odata-csdl-xml/odata-csdl-xml.html#ReferentialConstraint) without nullability requirement
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class ReferentialConstraint implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Common.v1.ReferentialConstraint';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        NavigationPropertyInterface::class,
    ];

        /** @param list<mixed> $value */
public function __construct(
        public readonly array $value = [],
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return new CollectionAnnotationValue(
            ...array_map(
                static fn($v) => new ConstantAnnotationValue('String', (string) $v),
                $this->value,
            ),
        );
    }
}
