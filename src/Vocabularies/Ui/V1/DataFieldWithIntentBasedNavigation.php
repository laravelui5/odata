<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Vocabularies\Common\V1\SemanticObjectMappingType;

/**
 * A piece of data that allows triggering intent-based UI navigation
 */
final readonly class DataFieldWithIntentBasedNavigation extends DataField
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.DataFieldWithIntentBasedNavigation';

    public const array FIELDS = [
        'Label' => ['label', 'Edm.String', false],
        'Criticality' => ['criticality', 'com.sap.vocabularies.UI.v1.CriticalityType', false],
        'CriticalityRepresentation' => ['criticalityRepresentation', 'com.sap.vocabularies.UI.v1.CriticalityRepresentationType', false],
        'IconUrl' => ['iconUrl', 'Edm.String', false],
        'Value' => ['value', 'Edm.PrimitiveType', true],
        'SemanticObject' => ['semanticObject', 'Edm.String', true],
        'Action' => ['action', 'Edm.String', false],
        'Mapping' => ['mapping', 'Collection(com.sap.vocabularies.Common.v1.SemanticObjectMappingType)', false],
    ];

    /**
     * @param list<SemanticObjectMappingType>|Path|null $mapping
     */
    public function __construct(
        string|Path|null $label = null,
        CriticalityType|Path|null $criticality = null,
        CriticalityRepresentationType|Path|null $criticalityRepresentation = null,
        string|Path|null $iconUrl = null,
        mixed $value = null,
        public string|Path|null $semanticObject = null,
        public string|Path|null $action = null,
        public array|Path|null $mapping = null,
    ) {
        parent::__construct(
            label: $label,
            criticality: $criticality,
            criticalityRepresentation: $criticalityRepresentation,
            iconUrl: $iconUrl,
            value: $value,
        );
    }
}
