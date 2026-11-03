<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Validation\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * Entities related via the single- or collection-valued navigation property identified by `path` are also related via the collection-valued navigation property identified by `target`.
 */
final readonly class ItemsOfType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Validation.V1.ItemsOfType';

    public const array FIELDS = [
        'path' => ['path', 'Edm.NavigationPropertyPath', true],
        'target' => ['target', 'Edm.NavigationPropertyPath', true],
    ];

    /**
     * @param string|Path|null $path A path to a single- or collection-valued navigation property
     * @param string|Path|null $target A path to a collection-valued navigation property
     */
    public function __construct(
        public string|Path|null $path = null,
        public string|Path|null $target = null,
    ) {}
}
