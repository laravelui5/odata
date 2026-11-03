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

    /**
     * @param float|Path|null $scaleFactor Display value in *ScaleFactor* units, e.g. 1000 for k (kilo), 1e6 for M (Mega)
     * @param int|Path|null $numberOfFractionalDigits Number of fractional digits of the scaled value to be visualized
     */
    public function __construct(
        public float|Path|null $scaleFactor = null,
        public int|Path|null $numberOfFractionalDigits = null,
    ) {}
}
