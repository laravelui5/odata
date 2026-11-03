<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Facet that refers to a thing perspective, e.g. LineItem
 */
final readonly class ReferenceFacet extends Facet
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.ReferenceFacet';

    public const array FIELDS = [
        'Label' => ['label', 'Edm.String', false],
        'ID' => ['id', 'Edm.String', false],
        'Target' => ['target', 'Edm.AnnotationPath', true],
    ];

    /**
     * @param string|Path|null $label Facet label
     * @param string|Path|null $id Unique identifier of a facet. ID should be stable, as long as the perceived semantics of the facet is unchanged.
     * @param string|Path|null $target Referenced information: Communication.Contact, Communication.Address, or a term that is tagged with UI.ThingPerspective, e.g. UI.StatusInfo, UI.LineItem, UI.Identification, UI.FieldGroup, UI.Badge
     */
    public function __construct(
        string|Path|null $label = null,
        string|Path|null $id = null,
        public string|Path|null $target = null,
    ) {
        parent::__construct(
            label: $label,
            id: $id,
        );
    }
}
