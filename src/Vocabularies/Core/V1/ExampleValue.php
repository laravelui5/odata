<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

readonly class ExampleValue extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Core.V1.ExampleValue';

    public const array FIELDS = [
        'Description' => ['description', 'Edm.String', false],
    ];

    public function __construct(
        public string|Path|null $description = null,
    ) {}
}
