<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class KPIType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.KPIType';

    public const array FIELDS = [
        'ID' => ['id', 'Edm.String', false],
        'ShortDescription' => ['shortDescription', 'Edm.String', false],
        'SelectionVariant' => ['selectionVariant', 'com.sap.vocabularies.UI.v1.SelectionVariantType', true],
        'DataPoint' => ['dataPoint', 'com.sap.vocabularies.UI.v1.DataPointType', true],
        'AdditionalDataPoints' => ['additionalDataPoints', 'Collection(com.sap.vocabularies.UI.v1.DataPointType)', false],
        'Detail' => ['detail', 'com.sap.vocabularies.UI.v1.KPIDetailType', false],
    ];

    /**
     * @param list<DataPointType>|Path|null $additionalDataPoints
     */
    public function __construct(
        public string|Path|null $id = null,
        public string|Path|null $shortDescription = null,
        public SelectionVariantType|Path|null $selectionVariant = null,
        public DataPointType|Path|null $dataPoint = null,
        public array|Path|null $additionalDataPoints = null,
        public KPIDetailType|Path|null $detail = null,
    ) {}
}
