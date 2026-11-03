<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ValueListMappingType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Common.v1.ValueListMappingType';

    public const array FIELDS = [
        'Label' => ['label', 'Edm.String', false],
        'CollectionPath' => ['collectionPath', 'Edm.String', true],
        'DistinctValuesSupported' => ['distinctValuesSupported', 'Edm.Boolean', false],
        'FetchValues' => ['fetchValues', 'Edm.Byte', false],
        'PresentationVariantQualifier' => ['presentationVariantQualifier', 'Edm.String', false],
        'SelectionVariantQualifier' => ['selectionVariantQualifier', 'Edm.String', false],
        'Parameters' => ['parameters', 'Collection(com.sap.vocabularies.Common.v1.ValueListParameter)', false],
    ];

    /**
     * @param string|Path|null $label Headline for value list, fallback is the label of the property or parameter
     * @param string|Path|null $collectionPath Resource path of an OData collection with possible values, relative to the document containing the value list mapping
     * @param bool|Path|null $distinctValuesSupported Indicates that the value list supports a 'distinct' aggregation on the value list properties defined via ValueListParameterInOut and ValueListParameterOut
     * @param int|Path|null $fetchValues Hint on when to fetch values
     * @param string|Path|null $presentationVariantQualifier Alternative representation of a value help, e.g. as a bar chart
     * @param string|Path|null $selectionVariantQualifier Optional combination of parameters and filters to query the value help entity set
     * @param list<ValueListParameter>|Path|null $parameters Instructions on how to construct the value list request and consume response properties
     */
    public function __construct(
        public string|Path|null $label = null,
        public string|Path|null $collectionPath = null,
        public bool|Path|null $distinctValuesSupported = null,
        public int|Path|null $fetchValues = null,
        public string|Path|null $presentationVariantQualifier = null,
        public string|Path|null $selectionVariantQualifier = null,
        public array|Path|null $parameters = null,
    ) {}
}
