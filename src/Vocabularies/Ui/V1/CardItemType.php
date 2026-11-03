<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Card structure for Grid List visualization. Header is always required. Details holds the card body as a collection of facets. Actions are optional card-level interactions.
 */
final readonly class CardItemType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.CardItemType';

    public const array FIELDS = [
        'Header' => ['header', 'Edm.AnnotationPath', true],
        'Actions' => ['actions', 'Collection(com.sap.vocabularies.UI.v1.DataFieldAbstract)', false],
        'Details' => ['details', 'Collection(com.sap.vocabularies.UI.v1.Facet)', false],
    ];

    /**
     * @param string|Path|null $header Points to a UI.HeaderInfo annotation defining the card header.
     * @param list<DataFieldAbstract>|Path|null $actions Action items for the card. Supports DataFieldForAction, DataFieldForIntentBasedNavigation, and DataFieldForActionGroup.
     * @param list<Facet>|Path|null $details Card body content as a collection of facets defining the sections of the card body.
     */
    public function __construct(
        public string|Path|null $header = null,
        public array|Path|null $actions = null,
        public array|Path|null $details = null,
    ) {}
}
