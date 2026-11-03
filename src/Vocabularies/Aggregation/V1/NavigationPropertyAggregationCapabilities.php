<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Aggregation\V1;

use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Vocabularies\Capabilities\V1\DeepInsertSupportType;
use LaravelUi5\OData\Vocabularies\Capabilities\V1\DeepUpdateSupportType;
use LaravelUi5\OData\Vocabularies\Capabilities\V1\DeleteRestrictionsType;
use LaravelUi5\OData\Vocabularies\Capabilities\V1\FilterRestrictionsType;
use LaravelUi5\OData\Vocabularies\Capabilities\V1\InsertRestrictionsType;
use LaravelUi5\OData\Vocabularies\Capabilities\V1\NavigationPropertyRestriction;
use LaravelUi5\OData\Vocabularies\Capabilities\V1\NavigationType;
use LaravelUi5\OData\Vocabularies\Capabilities\V1\ReadRestrictionsType;
use LaravelUi5\OData\Vocabularies\Capabilities\V1\SearchRestrictionsType;
use LaravelUi5\OData\Vocabularies\Capabilities\V1\SelectSupportType;
use LaravelUi5\OData\Vocabularies\Capabilities\V1\SortRestrictionsType;
use LaravelUi5\OData\Vocabularies\Capabilities\V1\UpdateRestrictionsType;

/**
 * Aggregation capabilities on a navigation path
 */
final readonly class NavigationPropertyAggregationCapabilities extends NavigationPropertyRestriction
{
    public const string TYPE = 'Org.OData.Aggregation.V1.NavigationPropertyAggregationCapabilities';

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
        'ApplySupported' => ['applySupported', 'Org.OData.Aggregation.V1.ApplySupportedType', false],
        'CustomAggregates' => ['customAggregates', 'Collection(Org.OData.Aggregation.V1.CustomAggregateType)', false],
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
     * @param ApplySupportedType|Path|null $applySupported Support for `$apply`
     * @param list<CustomAggregateType>|Path|null $customAggregates Supported custom aggregates
     */
    public function __construct(
        string|Path|null $navigationProperty = null,
        NavigationType|Path|null $navigability = null,
        array|Path|null $filterFunctions = null,
        FilterRestrictionsType|Path|null $filterRestrictions = null,
        SearchRestrictionsType|Path|null $searchRestrictions = null,
        SortRestrictionsType|Path|null $sortRestrictions = null,
        bool|Path|null $topSupported = null,
        bool|Path|null $skipSupported = null,
        SelectSupportType|Path|null $selectSupport = null,
        bool|Path|null $indexableByKey = null,
        InsertRestrictionsType|Path|null $insertRestrictions = null,
        DeepInsertSupportType|Path|null $deepInsertSupport = null,
        UpdateRestrictionsType|Path|null $updateRestrictions = null,
        DeepUpdateSupportType|Path|null $deepUpdateSupport = null,
        DeleteRestrictionsType|Path|null $deleteRestrictions = null,
        bool|Path|null $optimisticConcurrencyControl = null,
        ReadRestrictionsType|Path|null $readRestrictions = null,
        public ApplySupportedType|Path|null $applySupported = null,
        public array|Path|null $customAggregates = null,
    ) {
        parent::__construct(
            navigationProperty: $navigationProperty,
            navigability: $navigability,
            filterFunctions: $filterFunctions,
            filterRestrictions: $filterRestrictions,
            searchRestrictions: $searchRestrictions,
            sortRestrictions: $sortRestrictions,
            topSupported: $topSupported,
            skipSupported: $skipSupported,
            selectSupport: $selectSupport,
            indexableByKey: $indexableByKey,
            insertRestrictions: $insertRestrictions,
            deepInsertSupport: $deepInsertSupport,
            updateRestrictions: $updateRestrictions,
            deepUpdateSupport: $deepUpdateSupport,
            deleteRestrictions: $deleteRestrictions,
            optimisticConcurrencyControl: $optimisticConcurrencyControl,
            readRestrictions: $readRestrictions,
        );
    }
}
