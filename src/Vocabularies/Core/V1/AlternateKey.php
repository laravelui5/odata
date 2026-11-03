<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

final readonly class AlternateKey extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Core.V1.AlternateKey';

    public const array FIELDS = [
        'Key' => ['key', 'Collection(Org.OData.Core.V1.PropertyRef)', false],
    ];

    /**
     * @param list<PropertyRef>|Path|null $key
     */
    public function __construct(
        public array|Path|null $key = null,
    ) {}
}
