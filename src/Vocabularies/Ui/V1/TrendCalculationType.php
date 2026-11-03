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

    public function __construct(
        public mixed $referenceValue = null,
        public bool|Path|null $isRelativeDifference = null,
        public float|Path|null $upDifference = null,
        public float|Path|null $strongUpDifference = null,
        public float|Path|null $downDifference = null,
        public float|Path|null $strongDownDifference = null,
    ) {}
}
