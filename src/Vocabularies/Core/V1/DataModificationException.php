<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

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
 * A modification operation failed on the annotated instance or collection within a success payload
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_ALL)]
final readonly class DataModificationException implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Core.V1.DataModificationException';

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
     * @param MessageType|Path|null $info Information about the exception
     * @param DataModificationOperationKind|Path|null $failedOperation The kind of modification operation that failed
     * @param int|Path|null $responseCode Response code of the failed operation, e.g. 424 for a failed dependency
     */
    public function __construct(
        public MessageType|Path|null $info = null,
        public DataModificationOperationKind|Path|null $failedOperation = null,
        public int|Path|null $responseCode = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new DataModificationExceptionType(
            info: $this->info,
            failedOperation: $this->failedOperation,
            responseCode: $this->responseCode,
        ))->toRecord();
    }
}
