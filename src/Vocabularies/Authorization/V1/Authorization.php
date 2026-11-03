<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Authorization\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Base type for all Authorization types
 */
abstract readonly class Authorization extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Authorization.V1.Authorization';

    public const array FIELDS = [
        'Name' => ['name', 'Edm.String', true],
        'Description' => ['description', 'Edm.String', false],
    ];

    /**
     * @param string|Path|null $name Name that can be used to reference the authorization scheme
     * @param string|Path|null $description Description of the authorization scheme
     */
    public function __construct(
        public string|Path|null $name = null,
        public string|Path|null $description = null,
    ) {}
}
