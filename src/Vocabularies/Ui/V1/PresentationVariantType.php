<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Vocabularies\Common\V1\SortOrderType;

final readonly class PresentationVariantType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.PresentationVariantType';

    public const array FIELDS = [
        'ID' => ['id', 'Edm.String', false],
        'Text' => ['text', 'Edm.String', false],
        'MaxItems' => ['maxItems', 'Edm.Int32', false],
        'SortOrder' => ['sortOrder', 'Collection(com.sap.vocabularies.Common.v1.SortOrderType)', false],
        'GroupBy' => ['groupBy', 'Collection(Edm.PropertyPath)', false],
        'TotalBy' => ['totalBy', 'Collection(Edm.PropertyPath)', false],
        'Total' => ['total', 'Collection(Edm.PropertyPath)', false],
        'DynamicTotal' => ['dynamicTotal', 'Collection(Edm.AnnotationPath)', false],
        'IncludeGrandTotal' => ['includeGrandTotal', 'Edm.Boolean', false],
        'InitialExpansionLevel' => ['initialExpansionLevel', 'Edm.Int32', false],
        'Visualizations' => ['visualizations', 'Collection(Edm.AnnotationPath)', false],
        'RecursiveHierarchyQualifier' => ['recursiveHierarchyQualifier', 'Edm.String', false],
        'RequestAtLeast' => ['requestAtLeast', 'Collection(Edm.PropertyPath)', false],
        'SelectionFields' => ['selectionFields', 'Collection(Edm.PropertyPath)', false],
    ];

    /**
     * @param list<SortOrderType>|Path|null $sortOrder
     * @param list<string>|Path|null $groupBy
     * @param list<string>|Path|null $totalBy
     * @param list<string>|Path|null $total
     * @param list<string>|Path|null $dynamicTotal
     * @param list<string>|Path|null $visualizations
     * @param list<string>|Path|null $requestAtLeast
     * @param list<string>|Path|null $selectionFields
     */
    public function __construct(
        public string|Path|null $id = null,
        public string|Path|null $text = null,
        public int|Path|null $maxItems = null,
        public array|Path|null $sortOrder = null,
        public array|Path|null $groupBy = null,
        public array|Path|null $totalBy = null,
        public array|Path|null $total = null,
        public array|Path|null $dynamicTotal = null,
        public bool|Path|null $includeGrandTotal = null,
        public int|Path|null $initialExpansionLevel = null,
        public array|Path|null $visualizations = null,
        public string|Path|null $recursiveHierarchyQualifier = null,
        public array|Path|null $requestAtLeast = null,
        public array|Path|null $selectionFields = null,
    ) {}
}
