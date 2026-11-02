<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Discovery\Attributes;

use Attribute;

/**
 * The names of a discovered model in the OData service.
 *
 * `name` is the entity type (default: the class's short name), `entitySet` the set (default: the plural of the type
 * name, an English inflection — `Person` becomes `People`). Two models with the same short name in different
 * namespaces need one of them named.
 *
 * What the type declares is not set here: it is the table's columns and the model's relations, less what the model
 * never serializes (`$hidden`, `$visible`).
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class ODataEntity
{
    public function __construct(
        public ?string $name = null,
        public ?string $entitySet = null,
    ) {}
}
