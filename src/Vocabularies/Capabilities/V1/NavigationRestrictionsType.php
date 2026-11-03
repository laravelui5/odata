<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class NavigationRestrictionsType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Capabilities.V1.NavigationRestrictionsType';

    public const array FIELDS = [
        'Navigability' => ['navigability', 'Org.OData.Capabilities.V1.NavigationType', false],
        'RestrictedProperties' => ['restrictedProperties', 'Collection(Org.OData.Capabilities.V1.NavigationPropertyRestriction)', false],
    ];

    /**
     * @param NavigationType|Path|null $navigability Default navigability for all navigation properties of the annotation target. Individual navigation properties can override this value via `RestrictedProperties/Navigability`.
     * @param list<NavigationPropertyRestriction>|Path|null $restrictedProperties List of navigation properties with restrictions
     */
    public function __construct(
        public NavigationType|Path|null $navigability = null,
        public array|Path|null $restrictedProperties = null,
    ) {}
}
