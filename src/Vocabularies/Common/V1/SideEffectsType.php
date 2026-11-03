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
    ) {}
}
