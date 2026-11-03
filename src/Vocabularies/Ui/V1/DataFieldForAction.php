<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Triggers an OData action
 */
final readonly class DataFieldForAction extends DataFieldForActionAbstract
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.DataFieldForAction';

    public const array FIELDS = [
        'Label' => ['label', 'Edm.String', false],
        'Criticality' => ['criticality', 'com.sap.vocabularies.UI.v1.CriticalityType', false],
        'CriticalityRepresentation' => ['criticalityRepresentation', 'com.sap.vocabularies.UI.v1.CriticalityRepresentationType', false],
        'IconUrl' => ['iconUrl', 'Edm.String', false],
        'Inline' => ['inline', 'Edm.Boolean', false],
        'Determining' => ['determining', 'Edm.Boolean', false],
        'Action' => ['action', 'Edm.String', true],
        'InvocationGrouping' => ['invocationGrouping', 'com.sap.vocabularies.UI.v1.OperationGroupingType', false],
    ];

    /**
     * @param string|Path|null $label A short, human-readable text suitable for labels and captions in UIs
     * @param CriticalityType|Path|null $criticality Criticality of the data field value
     * @param CriticalityRepresentationType|Path|null $criticalityRepresentation Decides if criticality is visualized in addition by means of an icon
     * @param string|Path|null $iconUrl Optional icon
     * @param bool|Path|null $inline Action should be placed close to (or even inside) the visualized term
     * @param bool|Path|null $determining Determines whether the action completes a process step (e.g. approve, reject).
     * @param string|Path|null $action Name of an Action, Function, ActionImport, or FunctionImport in scope
     * @param OperationGroupingType|Path|null $invocationGrouping Expresses how invocations of this action on multiple instances should be grouped
     */
    public function __construct(
        string|Path|null $label = null,
        CriticalityType|Path|null $criticality = null,
        CriticalityRepresentationType|Path|null $criticalityRepresentation = null,
        string|Path|null $iconUrl = null,
        bool|Path|null $inline = null,
        bool|Path|null $determining = null,
        public string|Path|null $action = null,
        public OperationGroupingType|Path|null $invocationGrouping = null,
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
