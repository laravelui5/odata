<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ChartAxisAutoScaleBehaviorType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.ChartAxisAutoScaleBehaviorType';

    public const array FIELDS = [
        'ZeroAlwaysVisible' => ['zeroAlwaysVisible', 'Edm.Boolean', false],
        'DataScope' => ['dataScope', 'com.sap.vocabularies.UI.v1.ChartAxisAutoScaleDataScopeType', false],
    ];

    /**
     * @param bool|Path|null $zeroAlwaysVisible Forces the value axis to always display the zero value
     * @param ChartAxisAutoScaleDataScopeType|Path|null $dataScope Determines the automatic scaling
     */
    public function __construct(
        public bool|Path|null $zeroAlwaysVisible = null,
        public ChartAxisAutoScaleDataScopeType|Path|null $dataScope = null,
    ) {}
}
