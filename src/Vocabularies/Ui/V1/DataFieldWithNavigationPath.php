<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * A piece of data that allows navigating to related data
 */
final readonly class DataFieldWithNavigationPath extends DataField
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.DataFieldWithNavigationPath';

    public const array FIELDS = [
        'Label' => ['label', 'Edm.String', false],
        'Criticality' => ['criticality', 'com.sap.vocabularies.UI.v1.CriticalityType', false],
        'CriticalityRepresentation' => ['criticalityRepresentation', 'com.sap.vocabularies.UI.v1.CriticalityRepresentationType', false],
        'IconUrl' => ['iconUrl', 'Edm.String', false],
        'Value' => ['value', 'Edm.PrimitiveType', true],
        'Target' => ['target', 'Edm.NavigationPropertyPath', true],
    ];

    public function __construct(
        string|Path|null $label = null,
        CriticalityType|Path|null $criticality = null,
        CriticalityRepresentationType|Path|null $criticalityRepresentation = null,
        string|Path|null $iconUrl = null,
        mixed $value = null,
        public string|Path|null $target = null,
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
