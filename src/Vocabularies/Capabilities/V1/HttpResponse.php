<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class HttpResponse extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.HttpResponse';

    public const array FIELDS = [
        'StatusCode' => ['statusCode', 'Edm.String', true],
        'Description' => ['description', 'Edm.String', true],
    ];

    /**
     * @param string|Path|null $statusCode HTTP response status code, for example 400, 403, 501
     * @param string|Path|null $description Human-readable description of the response
     */
    public function __construct(
        public string|Path|null $statusCode = null,
        public string|Path|null $description = null,
    ) {}
}
