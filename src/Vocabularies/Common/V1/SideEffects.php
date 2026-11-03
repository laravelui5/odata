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
     * @param list<string>|Path|null $sourceProperties Changes to the values of one or more of these structural properties may affect the targets
     * @param list<string>|Path|null $sourceEntities Changes to one or more of these entities may affect the targets. An null value means the annotation target.
     * @param list<string>|Path|null $sourceEntitiesInserted Creating one or more of these entities may affect the targets.
     * @param list<string>|Path|null $sourceEntitiesUpdated Updating one or more of these entities may affect the targets.
     * @param list<string>|Path|null $sourceEntitiesDeleted Deleting one or more of these entities may affect the targets.
     * @param list<string>|Path|null $sourceEvents When the service raises one or more of these "events for side effects", the targets may be affected
     * @param list<string>|Path|null $targetProperties These structural properties may be affected if the value of one of the sources changes
     * @param list<string>|Path|null $targetEntities These entities will be affected if the value of one of the sources changes. All affected entities need to be explicitly listed. A null value means the annotation target.
     * @param string|Path|null $triggerAction Bound action to trigger side-effects after modifying an entity
     * @param bool|Path|null $discretionary Indicates whether the client can decide if a side-effect should be triggered or not
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

    protected function buildAnnotationValue(): AnnotationValueInterface
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
