<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\FunctionImportInterface;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionParameterInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LaravelUi5\OData\Edm\Contracts\SchemaInterface;
use LaravelUi5\OData\Edm\Contracts\Type\ComplexTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EnumMemberInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EnumTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeDefinitionInterface;

/**
 * Hints for resolving this error
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_ALL)]
final readonly class ErrorResolution implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Common.v1.ErrorResolution';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
        ComplexTypeInterface::class,
        EnumTypeInterface::class,
        EnumMemberInterface::class,
        TypeDefinitionInterface::class,
        PropertyInterface::class,
        NavigationPropertyInterface::class,
        EntitySetInterface::class,
        SingletonInterface::class,
        FunctionImportInterface::class,
        EntityContainerInterface::class,
        FunctionParameterInterface::class,
        FunctionInterface::class,
        SchemaInterface::class,
    ];

    /**
     * @param string|Path|null $analysis Short hint on how to analyze this error
     * @param string|Path|null $note Note for error resolution
     * @param string|Path|null $additionalNote Additional note for error resolution
     */
    public function __construct(
        public string|Path|null $analysis = null,
        public string|Path|null $note = null,
        public string|Path|null $additionalNote = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new ErrorResolutionType(
            analysis: $this->analysis,
            note: $this->note,
            additionalNote: $this->additionalNote,
        ))->toRecord();
    }
}
