<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class IntervalType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Common.v1.IntervalType';

    public const array FIELDS = [
        'Label' => ['label', 'Edm.String', false],
        'LowerBoundary' => ['lowerBoundary', 'Edm.PropertyPath', true],
        'LowerBoundaryIncluded' => ['lowerBoundaryIncluded', 'Edm.Boolean', false],
        'UpperBoundary' => ['upperBoundary', 'Edm.PropertyPath', true],
        'UpperBoundaryIncluded' => ['upperBoundaryIncluded', 'Edm.Boolean', false],
    ];

    public function __construct(
        public string|Path|null $label = null,
        public string|Path|null $lowerBoundary = null,
        public bool|Path|null $lowerBoundaryIncluded = null,
        public string|Path|null $upperBoundary = null,
        public bool|Path|null $upperBoundaryIncluded = null,
    ) {}
}
