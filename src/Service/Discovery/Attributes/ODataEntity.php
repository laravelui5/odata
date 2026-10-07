<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Discovery\Attributes;

use Attribute;

/**
 * Class-level discovery settings for an Eloquent model.
 *
 * `useHidden: true` keeps the model's `$hidden` out of the entity type: the
 * columns (and relations) the model never serializes are not declared in
 * `$metadata` either, so a client cannot `$filter` on them
 * (`$filter=startswith(password,'$2y')`). The key stays, whatever `$hidden`
 * says — an entity type without its key is not valid. Off by default, so an
 * existing service keeps its schema.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class ODataEntity
{
    public function __construct(
        public ?string $name = null,
        public ?string $entitySet = null,
        public bool $useHidden = false,
    ) {}
}
