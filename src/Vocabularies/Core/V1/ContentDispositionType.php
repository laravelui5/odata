<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ContentDispositionType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Core.V1.ContentDispositionType';

    public const array FIELDS = [
        'Type' => ['type', 'Edm.String', false],
        'Filename' => ['filename', 'Edm.String', false],
    ];

    public function __construct(
        public string|Path|null $type = null,
        public string|Path|null $filename = null,
    ) {}
}
