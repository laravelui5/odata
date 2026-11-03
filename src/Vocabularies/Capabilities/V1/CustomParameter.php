<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Vocabularies\Core\V1\PrimitiveExampleValue;

/**
 * A custom parameter is either a header or a query option
 */
final readonly class CustomParameter extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.CustomParameter';

    public const array FIELDS = [
        'Name' => ['name', 'Edm.String', true],
        'Description' => ['description', 'Edm.String', false],
        'DocumentationURL' => ['documentationURL', 'Edm.String', false],
        'Required' => ['required', 'Edm.Boolean', false],
        'ExampleValues' => ['exampleValues', 'Collection(Org.OData.Core.V1.PrimitiveExampleValue)', false],
    ];

    /**
     * @param list<PrimitiveExampleValue>|Path|null $exampleValues
     */
    public function __construct(
        public string|Path|null $name = null,
        public string|Path|null $description = null,
        public string|Path|null $documentationURL = null,
        public bool|Path|null $required = null,
        public array|Path|null $exampleValues = null,
    ) {}
}
