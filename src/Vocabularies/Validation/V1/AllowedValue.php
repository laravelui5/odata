<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Validation\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class AllowedValue extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Validation.V1.AllowedValue';

    public const array FIELDS = [
        'Value' => ['value', 'Edm.PrimitiveType', false],
    ];

    public function __construct(
        public mixed $value = null,
    ) {}
}
