<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class PropertyRef extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Core.V1.PropertyRef';

    public const array FIELDS = [
        'Name' => ['name', 'Edm.PropertyPath', true],
        'Alias' => ['alias', 'Edm.String', false],
    ];

    public function __construct(
        public string|Path|null $name = null,
        public string|Path|null $alias = null,
    ) {}
}
