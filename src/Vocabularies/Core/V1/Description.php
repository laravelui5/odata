<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EnumMemberInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EnumTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Container\FunctionImportInterface;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionParameterInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LaravelUi5\OData\Edm\Contracts\SchemaInterface;
use LaravelUi5\OData\Edm\Contracts\Type\ComplexTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeDefinitionInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;

/**
 * A brief description of a model element.
 *
 * Applies to all 14 annotation-target interfaces, so it may be placed on
 * any annotatable construct: types, properties, container members, function
 * parameters, and the schema itself.
 *
 * Hand-written proof-of-concept class that validates the full vocabulary
 * annotation stack end-to-end before the generator is run.
 *
 * @see TypedAnnotationInterface
 * @see OData CSDL XML v4.01 §14.2 (Annotation)
 * @see https://oasis-tcs.github.io/odata-vocabularies/vocabularies/Org.OData.Core.V1.xml
 */
#[Attribute(Attribute::TARGET_ALL)]
final readonly class Description implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Core.V1.Description';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
        ComplexTypeInterface::class,
        EnumTypeInterface::class,
        EnumMemberInterface::class,
        TypeDefinitionInterface::class,
        PropertyInterface::class,
        NavigationPropertyInterface::class,
        FunctionInterface::class,
        FunctionParameterInterface::class,
        EntitySetInterface::class,
        SingletonInterface::class,
        FunctionImportInterface::class,
        EntityContainerInterface::class,
        SchemaInterface::class,
    ];

    public function __construct(
        public readonly ?string $value = null,
        public readonly ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        if ($this->value === null) {
            return null;
        }
        return new ConstantAnnotationValue('String', (string) $this->value);
    }
}
