<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Reference period
 */
final readonly class ReferencePeriod extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.UI.v1.ReferencePeriod';

    public const array FIELDS = [
        'Description' => ['description', 'Edm.String', false],
        'Start' => ['start', 'Edm.DateTimeOffset', false],
        'End' => ['end', 'Edm.DateTimeOffset', false],
    ];

    /**
     * @param string|Path|null $description Short description of the reference period
     * @param string|Path|null $start Start of the reference period
     * @param string|Path|null $end End of the reference period
     */
    public function __construct(
        public string|Path|null $description = null,
        public string|Path|null $start = null,
        public string|Path|null $end = null,
    ) {}
}
