<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Vocabularies\Common\V1\SemanticObjectMappingType;

/**
 * Triggers intent-based UI navigation
 */
final readonly class DataFieldForIntentBasedNavigation extends DataFieldForActionAbstract
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.DataFieldForIntentBasedNavigation';

    public const array FIELDS = [
        'Label' => ['label', 'Edm.String', false],
        'Criticality' => ['criticality', 'com.sap.vocabularies.UI.v1.CriticalityType', false],
        'CriticalityRepresentation' => ['criticalityRepresentation', 'com.sap.vocabularies.UI.v1.CriticalityRepresentationType', false],
        'IconUrl' => ['iconUrl', 'Edm.String', false],
        'Inline' => ['inline', 'Edm.Boolean', false],
        'Determining' => ['determining', 'Edm.Boolean', false],
        'SemanticObject' => ['semanticObject', 'Edm.String', true],
        'Action' => ['action', 'Edm.String', false],
        'NavigationAvailable' => ['navigationAvailable', 'Edm.Boolean', false],
        'RequiresContext' => ['requiresContext', 'Edm.Boolean', false],
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
        bool|Path|null $inline = null,
        bool|Path|null $determining = null,
        public string|Path|null $semanticObject = null,
        public string|Path|null $action = null,
        public bool|Path|null $navigationAvailable = null,
        public bool|Path|null $requiresContext = null,
        public array|Path|null $mapping = null,
    ) {
        parent::__construct(
            label: $label,
            criticality: $criticality,
            criticalityRepresentation: $criticalityRepresentation,
            iconUrl: $iconUrl,
            inline: $inline,
            determining: $determining,
        );
    }
}
