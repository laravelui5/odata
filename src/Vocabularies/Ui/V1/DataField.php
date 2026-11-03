<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * A piece of data
 */
readonly class DataField extends DataFieldAbstract
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.DataField';

    public const array FIELDS = [
        'Label' => ['label', 'Edm.String', false],
        'Criticality' => ['criticality', 'com.sap.vocabularies.UI.v1.CriticalityType', false],
        'CriticalityRepresentation' => ['criticalityRepresentation', 'com.sap.vocabularies.UI.v1.CriticalityRepresentationType', false],
        'IconUrl' => ['iconUrl', 'Edm.String', false],
        'Value' => ['value', 'Edm.Untyped', true],
    ];

    /**
     * @param string|Path|null $label A short, human-readable text suitable for labels and captions in UIs
     * @param CriticalityType|Path|null $criticality Criticality of the data field value
     * @param CriticalityRepresentationType|Path|null $criticalityRepresentation Decides if criticality is visualized in addition by means of an icon
     * @param string|Path|null $iconUrl Optional icon
     * @param mixed $value The data field's value
     */
    public function __construct(
        string|Path|null $label = null,
        CriticalityType|Path|null $criticality = null,
        CriticalityRepresentationType|Path|null $criticalityRepresentation = null,
        string|Path|null $iconUrl = null,
        public mixed $value = null,
    ) {
        parent::__construct(
            label: $label,
            criticality: $criticality,
            criticalityRepresentation: $criticalityRepresentation,
            iconUrl: $iconUrl,
        );
    }
}
