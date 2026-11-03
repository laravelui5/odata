<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Triggers an action
 */
abstract readonly class DataFieldForActionAbstract extends DataFieldAbstract
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.DataFieldForActionAbstract';

    public const array FIELDS = [
        'Label' => ['label', 'Edm.String', false],
        'Criticality' => ['criticality', 'com.sap.vocabularies.UI.v1.CriticalityType', false],
        'CriticalityRepresentation' => ['criticalityRepresentation', 'com.sap.vocabularies.UI.v1.CriticalityRepresentationType', false],
        'IconUrl' => ['iconUrl', 'Edm.String', false],
        'Inline' => ['inline', 'Edm.Boolean', false],
        'Determining' => ['determining', 'Edm.Boolean', false],
    ];

    /**
     * @param string|Path|null $label A short, human-readable text suitable for labels and captions in UIs
     * @param CriticalityType|Path|null $criticality Criticality of the data field value
     * @param CriticalityRepresentationType|Path|null $criticalityRepresentation Decides if criticality is visualized in addition by means of an icon
     * @param string|Path|null $iconUrl Optional icon
     * @param bool|Path|null $inline Action should be placed close to (or even inside) the visualized term
     * @param bool|Path|null $determining Determines whether the action completes a process step (e.g. approve, reject).
     */
    public function __construct(
        string|Path|null $label = null,
        CriticalityType|Path|null $criticality = null,
        CriticalityRepresentationType|Path|null $criticalityRepresentation = null,
        string|Path|null $iconUrl = null,
        public bool|Path|null $inline = null,
        public bool|Path|null $determining = null,
    ) {
        parent::__construct(
            label: $label,
            criticality: $criticality,
            criticalityRepresentation: $criticalityRepresentation,
            iconUrl: $iconUrl,
        );
    }
}
