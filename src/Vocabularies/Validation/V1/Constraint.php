<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Validation\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionParameterInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Type\ComplexTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;

/**
 * Condition that the annotation target has to fulfill
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::TARGET_CLASS)]
final readonly class Constraint implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Validation.V1.Constraint';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        PropertyInterface::class,
        NavigationPropertyInterface::class,
        FunctionParameterInterface::class,
        EntityTypeInterface::class,
        ComplexTypeInterface::class,
    ];

    /**
     * @param string|Path|null $failureMessage Human-readable message that can be shown to end users if the constraint is not fulfilled
     * @param bool|Path|null $condition Value MUST be a dynamic expression that evaluates to true if and only if the constraint is fulfilled
     */
    public function __construct(
        public string|Path|null $failureMessage = null,
        public bool|Path|null $condition = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new ConstraintType(
            failureMessage: $this->failureMessage,
            condition: $this->condition,
        ))->toRecord();
    }
}
