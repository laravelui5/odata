<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class CountRestrictionsType extends CountRestrictionsBase
{
    public const string TYPE = 'Org.OData.Capabilities.V1.CountRestrictionsType';

    public const array FIELDS = [
        'Countable' => ['countable', 'Edm.Boolean', false],
        'NonCountableProperties' => ['nonCountableProperties', 'Collection(Edm.PropertyPath)', false],
        'NonCountableNavigationProperties' => ['nonCountableNavigationProperties', 'Collection(Edm.NavigationPropertyPath)', false],
    ];

    /**
     * @param list<string>|Path|null $nonCountableProperties
     * @param list<string>|Path|null $nonCountableNavigationProperties
     */
    public function __construct(
        bool|Path|null $countable = null,
        public array|Path|null $nonCountableProperties = null,
        public array|Path|null $nonCountableNavigationProperties = null,
    ) {
        parent::__construct(
            countable: $countable,
        );
    }
}
