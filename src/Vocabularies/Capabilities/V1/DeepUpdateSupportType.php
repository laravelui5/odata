<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class DeepUpdateSupportType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.DeepUpdateSupportType';

    public const array FIELDS = [
        'Supported' => ['supported', 'Edm.Boolean', false],
        'ContentIDSupported' => ['contentIDSupported', 'Edm.Boolean', false],
    ];

    public function __construct(
        public bool|Path|null $supported = null,
        public bool|Path|null $contentIDSupported = null,
    ) {}
}
