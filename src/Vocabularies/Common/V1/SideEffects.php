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
use LaravelUi5\OData\Edm\Contracts\FunctionInterface;
use LaravelUi5\OData\Edm\Contracts\Type\ComplexTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;

/**
 * Describes side-effects of modification operations
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final readonly class SideEffects implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.Common.v1.SideEffects';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
        EntityTypeInterface::class,
        ComplexTypeInterface::class,
        FunctionInterface::class,
    ];

    /**
     * @param list<string>|Path|null $sourceProperties
     * @param list<string>|Path|null $sourceEntities
     * @param list<string>|Path|null $sourceEntitiesInserted
     * @param list<string>|Path|null $sourceEntitiesUpdated
     * @param list<string>|Path|null $sourceEntitiesDeleted
     * @param list<string>|Path|null $sourceEvents
     * @param list<string>|Path|null $targetProperties
     * @param list<string>|Path|null $targetEntities
     */
    public function __construct(
        public array|Path|null $sourceProperties = null,
        public array|Path|null $sourceEntities = null,
        public array|Path|null $sourceEntitiesInserted = null,
        public array|Path|null $sourceEntitiesUpdated = null,
        public array|Path|null $sourceEntitiesDeleted = null,
        public array|Path|null $sourceEvents = null,
        public array|Path|null $targetProperties = null,
        public array|Path|null $targetEntities = null,
        public string|Path|null $triggerAction = null,
        public bool|Path|null $discretionary = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new SideEffectsType(
            sourceProperties: $this->sourceProperties,
            sourceEntities: $this->sourceEntities,
            sourceEntitiesInserted: $this->sourceEntitiesInserted,
            sourceEntitiesUpdated: $this->sourceEntitiesUpdated,
            sourceEntitiesDeleted: $this->sourceEntitiesDeleted,
            sourceEvents: $this->sourceEvents,
            targetProperties: $this->targetProperties,
            targetEntities: $this->targetEntities,
            triggerAction: $this->triggerAction,
            discretionary: $this->discretionary,
        ))->toRecord();
    }
}
