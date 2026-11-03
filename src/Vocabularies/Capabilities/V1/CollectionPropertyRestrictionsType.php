<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class CollectionPropertyRestrictionsType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.CollectionPropertyRestrictionsType';

    public const array FIELDS = [
        'CollectionProperty' => ['collectionProperty', 'Edm.PropertyPath', false],
        'FilterFunctions' => ['filterFunctions', 'Collection(Edm.String)', false],
        'FilterRestrictions' => ['filterRestrictions', 'Org.OData.Capabilities.V1.FilterRestrictionsType', false],
        'SearchRestrictions' => ['searchRestrictions', 'Org.OData.Capabilities.V1.SearchRestrictionsType', false],
        'SortRestrictions' => ['sortRestrictions', 'Org.OData.Capabilities.V1.SortRestrictionsType', false],
        'TopSupported' => ['topSupported', 'Edm.Boolean', false],
        'SkipSupported' => ['skipSupported', 'Edm.Boolean', false],
        'SelectSupport' => ['selectSupport', 'Org.OData.Capabilities.V1.SelectSupportType', false],
        'Insertable' => ['insertable', 'Edm.Boolean', false],
        'Updatable' => ['updatable', 'Edm.Boolean', false],
        'Deletable' => ['deletable', 'Edm.Boolean', false],
    ];

    /**
     * @param string|Path|null $collectionProperty Restricted Collection-valued property
     * @param list<string>|Path|null $filterFunctions List of functions and operators supported in filter expressions
     * @param FilterRestrictionsType|Path|null $filterRestrictions Restrictions on filter expressions
     * @param SearchRestrictionsType|Path|null $searchRestrictions Restrictions on search expressions
     * @param SortRestrictionsType|Path|null $sortRestrictions Restrictions on orderby expressions
     * @param bool|Path|null $topSupported Supports $top
     * @param bool|Path|null $skipSupported Supports $skip
     * @param SelectSupportType|Path|null $selectSupport Support for $select
     * @param bool|Path|null $insertable Members can be inserted into this collection
     * @param bool|Path|null $updatable Members of this ordered collection can be updated by ordinal
     * @param bool|Path|null $deletable Members of this ordered collection can be deleted by ordinal
     */
    public function __construct(
        public string|Path|null $collectionProperty = null,
        public array|Path|null $filterFunctions = null,
        public FilterRestrictionsType|Path|null $filterRestrictions = null,
        public SearchRestrictionsType|Path|null $searchRestrictions = null,
        public SortRestrictionsType|Path|null $sortRestrictions = null,
        public bool|Path|null $topSupported = null,
        public bool|Path|null $skipSupported = null,
        public SelectSupportType|Path|null $selectSupport = null,
        public bool|Path|null $insertable = null,
        public bool|Path|null $updatable = null,
        public bool|Path|null $deletable = null,
    ) {}
}
