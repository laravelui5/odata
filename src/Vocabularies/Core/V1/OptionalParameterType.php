<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class OptionalParameterType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Core.V1.OptionalParameterType';

    public const array FIELDS = [
        'DefaultValue' => ['defaultValue', 'Edm.String', false],
    ];

    public function __construct(
        public string|Path|null $defaultValue = null,
    ) {}
}
