<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Collection of OData actions and intent based navigations
 */
final readonly class DataFieldForActionGroup extends DataFieldAbstract
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.DataFieldForActionGroup';

    public const array FIELDS = [
        'Label' => ['label', 'Edm.String', false],
        'Criticality' => ['criticality', 'com.sap.vocabularies.UI.v1.CriticalityType', false],
        'CriticalityRepresentation' => ['criticalityRepresentation', 'com.sap.vocabularies.UI.v1.CriticalityRepresentationType', false],
        'IconUrl' => ['iconUrl', 'Edm.String', false],
        'ID' => ['id', 'Edm.String', false],
        'Actions' => ['actions', 'Collection(com.sap.vocabularies.UI.v1.DataFieldForActionAbstract)', false],
    ];

    /**
     * @param string|Path|null $label A short, human-readable text suitable for labels and captions in UIs
     * @param CriticalityType|Path|null $criticality Criticality of the data field value
     * @param CriticalityRepresentationType|Path|null $criticalityRepresentation Decides if criticality is visualized in addition by means of an icon
     * @param string|Path|null $iconUrl Optional icon
     * @param string|Path|null $id Identifier of an action group. ID should be stable, as long as the perceived semantics of the action group is unchanged.
     * @param list<DataFieldForActionAbstract>|Path|null $actions Collection of data fields that refer to actions or intent based navigations
     */
    public function __construct(
        string|Path|null $label = null,
        CriticalityType|Path|null $criticality = null,
        CriticalityRepresentationType|Path|null $criticalityRepresentation = null,
        string|Path|null $iconUrl = null,
        public string|Path|null $id = null,
        public array|Path|null $actions = null,
    ) {
        parent::__construct(
            label: $label,
            criticality: $criticality,
            criticalityRepresentation: $criticalityRepresentation,
            iconUrl: $iconUrl,
        );
    }
}
