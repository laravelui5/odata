<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\CollectionAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionParameterInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeDefinitionInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * Lists the MIME types acceptable for the annotated entity type marked with HasStream="true" or the annotated binary, stream, or string property or term
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final readonly class AcceptableMediaTypes implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Core.V1.AcceptableMediaTypes';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
        PropertyInterface::class,
        TypeDefinitionInterface::class,
        FunctionParameterInterface::class,
    ];

        /** @param list<string> $value */
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
