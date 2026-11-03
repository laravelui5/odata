<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Collection of facets
 */
final readonly class CollectionFacet extends Facet
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.CollectionFacet';

    public const array FIELDS = [
        'Label' => ['label', 'Edm.String', false],
        'ID' => ['id', 'Edm.String', false],
        'Facets' => ['facets', 'Collection(com.sap.vocabularies.UI.v1.Facet)', false],
    ];

    /**
     * @param list<Facet>|Path|null $facets
     */
    public function __construct(
        string|Path|null $label = null,
        string|Path|null $id = null,
        public array|Path|null $facets = null,
    ) {
        parent::__construct(
            label: $label,
            id: $id,
        );
    }
}
