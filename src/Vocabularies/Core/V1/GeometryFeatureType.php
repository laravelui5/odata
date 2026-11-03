<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

use LaravelUi5\OData\Edm\Annotation\AbstractRecord;
use LaravelUi5\OData\Edm\Annotation\Path;

/**
 * A [Feature Object](https://datatracker.ietf.org/doc/html/rfc7946#section-3.2) represents a spatially bounded thing
 */
final readonly class GeometryFeatureType extends AbstractRecord
{
    public const string TYPE = 'Org.OData.Core.V1.GeometryFeatureType';

    public const array FIELDS = [
        'geometry' => ['geometry', 'Edm.Geometry', false],
        'properties' => ['properties', 'Org.OData.Core.V1.Dictionary', false],
        'id' => ['id', 'Edm.String', false],
    ];

    public function __construct(
        public mixed $geometry = null,
        public Dictionary|Path|null $properties = null,
        public string|Path|null $id = null,
    ) {}
}
