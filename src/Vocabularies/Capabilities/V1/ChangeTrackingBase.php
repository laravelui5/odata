<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

readonly class ChangeTrackingBase extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.ChangeTrackingBase';

    public const array FIELDS = [
        'Supported' => ['supported', 'Edm.Boolean', false],
    ];

    /**
     * @param bool|Path|null $supported odata.track-changes preference is supported
     */
    public function __construct(
        public bool|Path|null $supported = null,
    ) {}
}
