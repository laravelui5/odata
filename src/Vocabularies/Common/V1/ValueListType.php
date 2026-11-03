<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Exactly one of `CollectionPath` and `RelativeCollectionPath` must be provided.
 */
final readonly class ValueListType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Common.v1.ValueListType';

    public const array FIELDS = [
        'Label' => ['label', 'Edm.String', false],
        'CollectionPath' => ['collectionPath', 'Edm.String', false],
        'RelativeCollectionPath' => ['relativeCollectionPath', 'Edm.NavigationPropertyPath', false],
        'CollectionRoot' => ['collectionRoot', 'Edm.String', false],
        'DistinctValuesSupported' => ['distinctValuesSupported', 'Edm.Boolean', false],
        'SearchSupported' => ['searchSupported', 'Edm.Boolean', false],
        'FetchValues' => ['fetchValues', 'Edm.Byte', false],
        'PresentationVariantQualifier' => ['presentationVariantQualifier', 'Edm.String', false],
        'SelectionVariantQualifier' => ['selectionVariantQualifier', 'Edm.String', false],
        'Parameters' => ['parameters', 'Collection(com.sap.vocabularies.Common.v1.ValueListParameter)', false],
    ];

    /**
     * @param string|Path|null $label Headline for value list, fallback is the label of the property or parameter
     * @param string|Path|null $collectionPath Resource path of an OData collection with possible values, relative to CollectionRoot
     * @param string|Path|null $relativeCollectionPath Navigation property path of an OData collection with possible values, relative to the annotation target
     * @param string|Path|null $collectionRoot Service root of the value list collection; not specified means local to the document containing the annotation
     * @param bool|Path|null $distinctValuesSupported Indicates that the value list supports a 'distinct' aggregation on the value list properties defined via ValueListParameterInOut and ValueListParameterOut
     * @param bool|Path|null $searchSupported Value list supports the $search query option
     * @param int|Path|null $fetchValues Hint on when to fetch values
     * @param string|Path|null $presentationVariantQualifier Alternative representation of a value help, e.g. as a bar chart
     * @param string|Path|null $selectionVariantQualifier Optional combination of parameters and filters to query the value help entity set
     * @param list<ValueListParameter>|Path|null $parameters Instructions on how to construct the value list request and consume response properties
     */
    public function __construct(
        public string|Path|null $label = null,
        public string|Path|null $collectionPath = null,
        public string|Path|null $relativeCollectionPath = null,
        public string|Path|null $collectionRoot = null,
        public bool|Path|null $distinctValuesSupported = null,
        public bool|Path|null $searchSupported = null,
        public int|Path|null $fetchValues = null,
        public string|Path|null $presentationVariantQualifier = null,
        public string|Path|null $selectionVariantQualifier = null,
        public array|Path|null $parameters = null,
    ) {}
}
