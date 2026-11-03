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
     * @param string|Path|null $id Optional identifier to reference this instance from an external context
     * @param string|Path|null $shortDescription Very short description
     * @param SelectionVariantType|Path|null $selectionVariant Selection variant, either specified inline or referencing another annotation via Path
     * @param DataPointType|Path|null $dataPoint Data point, either specified inline or referencing another annotation via Path
     * @param list<DataPointType>|Path|null $additionalDataPoints Additional data points, either specified inline or referencing another annotation via Path
     * @param KPIDetailType|Path|null $detail Contains information about KPI details, especially drill-down presentations
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
