<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Changes to the source properties or source entities may have side-effects on the target properties or entities.
 */
final readonly class SideEffectsType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Common.v1.SideEffectsType';

    public const array FIELDS = [
        'SourceProperties' => ['sourceProperties', 'Collection(Edm.PropertyPath)', false],
        'SourceEntities' => ['sourceEntities', 'Collection(Edm.NavigationPropertyPath)', false],
        'SourceEntitiesInserted' => ['sourceEntitiesInserted', 'Collection(Edm.NavigationPropertyPath)', false],
        'SourceEntitiesUpdated' => ['sourceEntitiesUpdated', 'Collection(Edm.NavigationPropertyPath)', false],
        'SourceEntitiesDeleted' => ['sourceEntitiesDeleted', 'Collection(Edm.NavigationPropertyPath)', false],
        'SourceEvents' => ['sourceEvents', 'Collection(Edm.String)', false],
        'TargetProperties' => ['targetProperties', 'Collection(Edm.String)', false],
        'TargetEntities' => ['targetEntities', 'Collection(Edm.NavigationPropertyPath)', false],
        'TriggerAction' => ['triggerAction', 'Edm.String', false],
        'Discretionary' => ['discretionary', 'Edm.Boolean', false],
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
    ) {}
}
