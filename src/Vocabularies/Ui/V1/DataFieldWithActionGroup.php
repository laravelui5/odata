<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Collection of OData actions and intent based navigations
 */
final readonly class DataFieldWithActionGroup extends DataField
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.DataFieldWithActionGroup';

    public const array FIELDS = [
        'Label' => ['label', 'Edm.String', false],
        'Criticality' => ['criticality', 'com.sap.vocabularies.UI.v1.CriticalityType', false],
        'CriticalityRepresentation' => ['criticalityRepresentation', 'com.sap.vocabularies.UI.v1.CriticalityRepresentationType', false],
        'IconUrl' => ['iconUrl', 'Edm.String', false],
        'Value' => ['value', 'Edm.PrimitiveType', true],
        'Actions' => ['actions', 'Collection(com.sap.vocabularies.UI.v1.DataField)', false],
    ];

    /**
     * @param list<DataField>|Path|null $actions
     */
    public function __construct(
        string|Path|null $label = null,
        CriticalityType|Path|null $criticality = null,
        CriticalityRepresentationType|Path|null $criticalityRepresentation = null,
        string|Path|null $iconUrl = null,
        mixed $value = null,
        public array|Path|null $actions = null,
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
