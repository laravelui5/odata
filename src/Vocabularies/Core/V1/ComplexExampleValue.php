<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ComplexExampleValue extends ExampleValue
{
    public const string TYPE = 'Org.OData.Core.V1.ComplexExampleValue';

    public const array FIELDS = [
        'Description' => ['description', 'Edm.String', false],
        'Value' => ['value', 'Edm.ComplexType', true],
    ];

    /**
     * @param string|Path|null $description Description of the example value
     * @param mixed $value Example value for the custom parameter
     */
    public function __construct(
        string|Path|null $description = null,
        public mixed $value = null,
    ) {
        parent::__construct(
            description: $description,
        );
    }
}
