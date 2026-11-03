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
     * @param list<ValueListParameter>|Path|null $parameters
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
