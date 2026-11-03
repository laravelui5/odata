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

    /**
     * @param string|Path|null $id Protocol Identifier
     * @param string|Path|null $urlTemplate URL Template including parameters. Parameters are enclosed in curly braces {} as defined in RFC6570
     * @param string|Path|null $documentationUrl Human readable description of the meaning of the URL Template parameters
     */
    public function __construct(
        public string|Path|null $id = null,
        public string|Path|null $urlTemplate = null,
        public string|Path|null $documentationUrl = null,
    ) {}
}
