<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Facet that refers to a URL
 */
final readonly class ReferenceURLFacet extends Facet
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.ReferenceURLFacet';

    public const array FIELDS = [
        'Label' => ['label', 'Edm.String', false],
        'ID' => ['id', 'Edm.String', false],
        'Url' => ['url', 'Edm.String', true],
        'UrlContentType' => ['urlContentType', 'Edm.String', false],
    ];

    /**
     * @param string|Path|null $label Facet label
     * @param string|Path|null $id Unique identifier of a facet. ID should be stable, as long as the perceived semantics of the facet is unchanged.
     * @param string|Path|null $url URL of referenced information
     * @param string|Path|null $urlContentType Media type of referenced information
     */
    public function __construct(
        string|Path|null $label = null,
        string|Path|null $id = null,
        public string|Path|null $url = null,
        public string|Path|null $urlContentType = null,
    ) {
        parent::__construct(
            label: $label,
            id: $id,
        );
    }
}
