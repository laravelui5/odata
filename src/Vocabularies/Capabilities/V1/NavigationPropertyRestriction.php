<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

readonly class NavigationPropertyRestriction extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.NavigationPropertyRestriction';

    public const array FIELDS = [
        'NavigationProperty' => ['navigationProperty', 'Edm.NavigationPropertyPath', true],
        'Navigability' => ['navigability', 'Org.OData.Capabilities.V1.NavigationType', false],
        'FilterFunctions' => ['filterFunctions', 'Collection(Edm.String)', false],
        'FilterRestrictions' => ['filterRestrictions', 'Org.OData.Capabilities.V1.FilterRestrictionsType', false],
        'SearchRestrictions' => ['searchRestrictions', 'Org.OData.Capabilities.V1.SearchRestrictionsType', false],
        'SortRestrictions' => ['sortRestrictions', 'Org.OData.Capabilities.V1.SortRestrictionsType', false],
        'TopSupported' => ['topSupported', 'Edm.Boolean', false],
        'SkipSupported' => ['skipSupported', 'Edm.Boolean', false],
        'SelectSupport' => ['selectSupport', 'Org.OData.Capabilities.V1.SelectSupportType', false],
        'IndexableByKey' => ['indexableByKey', 'Edm.Boolean', false],
        'InsertRestrictions' => ['insertRestrictions', 'Org.OData.Capabilities.V1.InsertRestrictionsType', false],
        'DeepInsertSupport' => ['deepInsertSupport', 'Org.OData.Capabilities.V1.DeepInsertSupportType', false],
        'UpdateRestrictions' => ['updateRestrictions', 'Org.OData.Capabilities.V1.UpdateRestrictionsType', false],
        'DeepUpdateSupport' => ['deepUpdateSupport', 'Org.OData.Capabilities.V1.DeepUpdateSupportType', false],
        'DeleteRestrictions' => ['deleteRestrictions', 'Org.OData.Capabilities.V1.DeleteRestrictionsType', false],
        'OptimisticConcurrencyControl' => ['optimisticConcurrencyControl', 'Edm.Boolean', false],
        'ReadRestrictions' => ['readRestrictions', 'Org.OData.Capabilities.V1.ReadRestrictionsType', false],
    ];

    /**
     * @param string|Path|null $navigationProperty Navigation properties can be navigated
     * @param NavigationType|Path|null $navigability Supported navigability of this navigation property
     * @param list<string>|Path|null $filterFunctions List of functions and operators supported in filter expressions
     * @param FilterRestrictionsType|Path|null $filterRestrictions Restrictions on filter expressions
     * @param SearchRestrictionsType|Path|null $searchRestrictions Restrictions on search expressions
     * @param SortRestrictionsType|Path|null $sortRestrictions Restrictions on orderby expressions
     * @param bool|Path|null $topSupported Supports $top
     * @param bool|Path|null $skipSupported Supports $skip
     * @param SelectSupportType|Path|null $selectSupport Support for $select
     * @param bool|Path|null $indexableByKey Supports key values according to OData URL conventions
     * @param InsertRestrictionsType|Path|null $insertRestrictions Restrictions on insert operations
     * @param DeepInsertSupportType|Path|null $deepInsertSupport Deep Insert Support of the annotated resource (the whole service, an entity set, or a collection-valued resource)
     * @param UpdateRestrictionsType|Path|null $updateRestrictions Restrictions on update operations
     * @param DeepUpdateSupportType|Path|null $deepUpdateSupport Deep Update Support of the annotated resource (the whole service, an entity set, or a collection-valued resource)
     * @param DeleteRestrictionsType|Path|null $deleteRestrictions Restrictions on delete operations
     * @param bool|Path|null $optimisticConcurrencyControl Data modification (including insert) along this navigation property requires the use of ETags
     * @param ReadRestrictionsType|Path|null $readRestrictions Restrictions for retrieving entities
     */
    public function __construct(
        public string|Path|null $navigationProperty = null,
        public NavigationType|Path|null $navigability = null,
        public array|Path|null $filterFunctions = null,
        public FilterRestrictionsType|Path|null $filterRestrictions = null,
        public SearchRestrictionsType|Path|null $searchRestrictions = null,
        public SortRestrictionsType|Path|null $sortRestrictions = null,
        public bool|Path|null $topSupported = null,
        public bool|Path|null $skipSupported = null,
        public SelectSupportType|Path|null $selectSupport = null,
        public bool|Path|null $indexableByKey = null,
        public InsertRestrictionsType|Path|null $insertRestrictions = null,
        public DeepInsertSupportType|Path|null $deepInsertSupport = null,
        public UpdateRestrictionsType|Path|null $updateRestrictions = null,
        public DeepUpdateSupportType|Path|null $deepUpdateSupport = null,
        public DeleteRestrictionsType|Path|null $deleteRestrictions = null,
        public bool|Path|null $optimisticConcurrencyControl = null,
        public ReadRestrictionsType|Path|null $readRestrictions = null,
    ) {}
}
