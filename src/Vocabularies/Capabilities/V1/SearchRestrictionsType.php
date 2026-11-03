<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class SearchRestrictionsType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.SearchRestrictionsType';

    public const array FIELDS = [
        'Searchable' => ['searchable', 'Edm.Boolean', false],
        'UnsupportedExpressions' => ['unsupportedExpressions', 'Org.OData.Capabilities.V1.SearchExpressions', false],
    ];

    /**
     * @param SearchExpressions|list<SearchExpressions>|Path|null $unsupportedExpressions
     */
    public function __construct(
        public bool|Path|null $searchable = null,
        public SearchExpressions|array|Path|null $unsupportedExpressions = null,
    ) {}
}
