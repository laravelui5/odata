<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class CallbackProtocol extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.CallbackProtocol';

    public const array FIELDS = [
        'Id' => ['id', 'Edm.String', false],
        'UrlTemplate' => ['urlTemplate', 'Edm.String', false],
        'DocumentationUrl' => ['documentationUrl', 'Edm.String', false],
    ];

    public function __construct(
        public string|Path|null $id = null,
        public string|Path|null $urlTemplate = null,
        public string|Path|null $documentationUrl = null,
    ) {}
}
