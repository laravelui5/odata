<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class ChangeTrackingType extends ChangeTrackingBase
{
    public const string TYPE = 'Org.OData.Capabilities.V1.ChangeTrackingType';

    public const array FIELDS = [
        'Supported' => ['supported', 'Edm.Boolean', false],
        'FilterableProperties' => ['filterableProperties', 'Collection(Edm.PropertyPath)', false],
        'ExpandableProperties' => ['expandableProperties', 'Collection(Edm.NavigationPropertyPath)', false],
    ];

    /**
     * @param bool|Path|null $supported odata.track-changes preference is supported
     * @param list<string>|Path|null $filterableProperties Change tracking supports filters on these properties
     * @param list<string>|Path|null $expandableProperties Change tracking supports these properties expanded
     */
    public function __construct(
        bool|Path|null $supported = null,
        public array|Path|null $filterableProperties = null,
        public array|Path|null $expandableProperties = null,
    ) {
        parent::__construct(
            supported: $supported,
        );
    }
}
