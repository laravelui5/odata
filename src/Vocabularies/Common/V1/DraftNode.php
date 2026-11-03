<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;

/**
 * Entities in this set are parts of business documents that support the draft pattern
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class DraftNode implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Common.v1.DraftNode';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
    ];

    /**
     * @param string|Path|null $preparationAction Action that prepares a draft document for later activation
     * @param string|Path|null $validationFunction Function that validates whether a draft document is ready for activation
     */
    public function __construct(
        public string|Path|null $preparationAction = null,
        public string|Path|null $validationFunction = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new DraftNodeType(
            preparationAction: $this->preparationAction,
            validationFunction: $this->validationFunction,
        ))->toRecord();
    }
}
