<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Abstract base type for facets
 */
abstract readonly class Facet extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.Facet';

    public const array FIELDS = [
        'Label' => ['label', 'Edm.String', false],
        'ID' => ['id', 'Edm.String', false],
    ];

    /**
     * @param string|Path|null $label Facet label
     * @param string|Path|null $id Unique identifier of a facet. ID should be stable, as long as the perceived semantics of the facet is unchanged.
     */
    public function __construct(
        public string|Path|null $label = null,
        public string|Path|null $id = null,
    ) {}
}
