<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Describes how to calculate the trend of a value
 */
final readonly class TrendCalculationType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.TrendCalculationType';

    public const array FIELDS = [
        'ReferenceValue' => ['referenceValue', 'Edm.PrimitiveType', true],
        'IsRelativeDifference' => ['isRelativeDifference', 'Edm.Boolean', false],
        'UpDifference' => ['upDifference', 'Edm.Decimal', true],
        'StrongUpDifference' => ['strongUpDifference', 'Edm.Decimal', true],
        'DownDifference' => ['downDifference', 'Edm.Decimal', true],
        'StrongDownDifference' => ['strongDownDifference', 'Edm.Decimal', true],
    ];

    /**
     * @param mixed $referenceValue Reference value for the calculation, e.g. number of sales for the last year
     * @param bool|Path|null $isRelativeDifference Calculate with a relative difference
     * @param float|Path|null $upDifference Threshold for Up
     * @param float|Path|null $strongUpDifference Threshold for StrongUp
     * @param float|Path|null $downDifference Threshold for Down
     * @param float|Path|null $strongDownDifference Threshold for StrongDown
     */
    public function __construct(
        public mixed $referenceValue = null,
        public bool|Path|null $isRelativeDifference = null,
        public float|Path|null $upDifference = null,
        public float|Path|null $strongUpDifference = null,
        public float|Path|null $downDifference = null,
        public float|Path|null $strongDownDifference = null,
    ) {}
}
