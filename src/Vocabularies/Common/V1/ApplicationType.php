<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ApplicationType extends AbstractRecord
{
    public const string TYPE = 'com.sap.vocabularies.Common.v1.ApplicationType';

    public const array FIELDS = [
        'Component' => ['component', 'Edm.String', false],
        'ServiceRepository' => ['serviceRepository', 'Edm.String', false],
        'ServiceId' => ['serviceId', 'Edm.String', false],
        'ServiceVersion' => ['serviceVersion', 'Edm.String', false],
    ];

    public function __construct(
        public string|Path|null $component = null,
        public string|Path|null $serviceRepository = null,
        public string|Path|null $serviceId = null,
        public string|Path|null $serviceVersion = null,
    ) {}
}
