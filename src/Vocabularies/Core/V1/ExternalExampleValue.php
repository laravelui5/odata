<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ExternalExampleValue extends ExampleValue
{
    public const string TYPE = 'Org.OData.Core.V1.ExternalExampleValue';

    public const array FIELDS = [
        'Description' => ['description', 'Edm.String', false],
        'ExternalValue' => ['externalValue', 'Edm.String', true],
    ];

    /**
     * @param string|Path|null $description Description of the example value
     * @param string|Path|null $externalValue Url reference to the value in its literal format
     */
    public function __construct(
        string|Path|null $description = null,
        public string|Path|null $externalValue = null,
    ) {
        parent::__construct(
            description: $description,
        );
    }
}
