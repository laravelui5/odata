<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class DefaultCapabilitiesType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.DefaultCapabilitiesType';

    public const array FIELDS = [
        'ChangeTracking' => ['changeTracking', 'Org.OData.Capabilities.V1.ChangeTrackingBase', false],
        'CountRestrictions' => ['countRestrictions', 'Org.OData.Capabilities.V1.CountRestrictionsBase', false],
        'IndexableByKey' => ['indexableByKey', 'Edm.Boolean', false],
        'TopSupported' => ['topSupported', 'Edm.Boolean', false],
        'SkipSupported' => ['skipSupported', 'Edm.Boolean', false],
        'ComputeSupported' => ['computeSupported', 'Edm.Boolean', false],
        'SelectSupport' => ['selectSupport', 'Org.OData.Capabilities.V1.SelectSupportType', false],
        'FilterRestrictions' => ['filterRestrictions', 'Org.OData.Capabilities.V1.FilterRestrictionsBase', false],
        'SortRestrictions' => ['sortRestrictions', 'Org.OData.Capabilities.V1.SortRestrictionsBase', false],
        'ExpandRestrictions' => ['expandRestrictions', 'Org.OData.Capabilities.V1.ExpandRestrictionsBase', false],
        'SearchRestrictions' => ['searchRestrictions', 'Org.OData.Capabilities.V1.SearchRestrictionsType', false],
        'InsertRestrictions' => ['insertRestrictions', 'Org.OData.Capabilities.V1.InsertRestrictionsBase', false],
        'UpdateRestrictions' => ['updateRestrictions', 'Org.OData.Capabilities.V1.UpdateRestrictionsBase', false],
        'DeleteRestrictions' => ['deleteRestrictions', 'Org.OData.Capabilities.V1.DeleteRestrictionsBase', false],
        'OperationRestrictions' => ['operationRestrictions', 'Org.OData.Capabilities.V1.OperationRestrictionsType', false],
        'ReadRestrictions' => ['readRestrictions', 'Org.OData.Capabilities.V1.ReadRestrictionsType', false],
    ];

    /**
     * @param ChangeTrackingBase|Path|null $changeTracking Change tracking capabilities
     * @param CountRestrictionsBase|Path|null $countRestrictions Restrictions on /$count path suffix and $count=true system query option
     * @param bool|Path|null $indexableByKey Supports key values according to OData URL conventions
     * @param bool|Path|null $topSupported Supports $top
     * @param bool|Path|null $skipSupported Supports $skip
     * @param bool|Path|null $computeSupported Supports $compute
     * @param SelectSupportType|Path|null $selectSupport Support for $select and nested query options within $select
     * @param FilterRestrictionsBase|Path|null $filterRestrictions Restrictions on filter expressions
     * @param SortRestrictionsBase|Path|null $sortRestrictions Restrictions on orderby expressions
     * @param ExpandRestrictionsBase|Path|null $expandRestrictions Restrictions on expand expressions
     * @param SearchRestrictionsType|Path|null $searchRestrictions Restrictions on search expressions
     * @param InsertRestrictionsBase|Path|null $insertRestrictions Restrictions on insert operations
     * @param UpdateRestrictionsBase|Path|null $updateRestrictions Restrictions on update operations
     * @param DeleteRestrictionsBase|Path|null $deleteRestrictions Restrictions on delete operations
     * @param OperationRestrictionsType|Path|null $operationRestrictions Restrictions for function or action operations
     * @param ReadRestrictionsType|Path|null $readRestrictions Restrictions for retrieving a collection of entities, retrieving a singleton instance
     */
    public function __construct(
        public ChangeTrackingBase|Path|null $changeTracking = null,
        public CountRestrictionsBase|Path|null $countRestrictions = null,
        public bool|Path|null $indexableByKey = null,
        public bool|Path|null $topSupported = null,
        public bool|Path|null $skipSupported = null,
        public bool|Path|null $computeSupported = null,
        public SelectSupportType|Path|null $selectSupport = null,
        public FilterRestrictionsBase|Path|null $filterRestrictions = null,
        public SortRestrictionsBase|Path|null $sortRestrictions = null,
        public ExpandRestrictionsBase|Path|null $expandRestrictions = null,
        public SearchRestrictionsType|Path|null $searchRestrictions = null,
        public InsertRestrictionsBase|Path|null $insertRestrictions = null,
        public UpdateRestrictionsBase|Path|null $updateRestrictions = null,
        public DeleteRestrictionsBase|Path|null $deleteRestrictions = null,
        public OperationRestrictionsType|Path|null $operationRestrictions = null,
        public ReadRestrictionsType|Path|null $readRestrictions = null,
    ) {}
}
