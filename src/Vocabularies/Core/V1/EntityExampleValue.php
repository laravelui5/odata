<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class EntityExampleValue extends ExampleValue
{
    public const string TYPE = 'Org.OData.Core.V1.EntityExampleValue';

    public const array FIELDS = [
        'Description' => ['description', 'Edm.String', false],
    ];
}
