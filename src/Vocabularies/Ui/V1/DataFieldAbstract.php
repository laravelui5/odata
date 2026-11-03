<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Elementary building block that represents a piece of data and/or allows triggering an action
 */
abstract readonly class DataFieldAbstract extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.DataFieldAbstract';

    public const array FIELDS = [
        'Label' => ['label', 'Edm.String', false],
        'Criticality' => ['criticality', 'com.sap.vocabularies.UI.v1.CriticalityType', false],
        'CriticalityRepresentation' => ['criticalityRepresentation', 'com.sap.vocabularies.UI.v1.CriticalityRepresentationType', false],
        'IconUrl' => ['iconUrl', 'Edm.String', false],
    ];

    /**
     * @param string|Path|null $label A short, human-readable text suitable for labels and captions in UIs
     * @param CriticalityType|Path|null $criticality Criticality of the data field value
     * @param CriticalityRepresentationType|Path|null $criticalityRepresentation Decides if criticality is visualized in addition by means of an icon
     * @param string|Path|null $iconUrl Optional icon
     */
    public function __construct(
        public string|Path|null $label = null,
        public CriticalityType|Path|null $criticality = null,
        public CriticalityRepresentationType|Path|null $criticalityRepresentation = null,
        public string|Path|null $iconUrl = null,
    ) {}
}
