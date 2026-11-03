<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Describes how to visualise a number
 */
final readonly class NumberFormat extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.NumberFormat';

    public const array FIELDS = [
        'ScaleFactor' => ['scaleFactor', 'Edm.Decimal', false],
        'NumberOfFractionalDigits' => ['numberOfFractionalDigits', 'Edm.Byte', false],
    ];

    public function __construct(
        public float|Path|null $scaleFactor = null,
        public int|Path|null $numberOfFractionalDigits = null,
    ) {}
}
