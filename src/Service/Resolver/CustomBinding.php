<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Resolver;

use LaravelUi5\OData\Service\Contracts\EntitySetResolverInterface;
use LaravelUi5\OData\Service\Contracts\ResolverBindingInterface;

/**
 * Serializable binding for a custom EntitySetResolverInterface implementation.
 *
 * Stores the resolver class-string and resolves it from the Laravel container
 * at runtime, so dependencies (auth, config, services, etc.) are injected.
 *
 * Use this for entity sets that don't map to a single Eloquent model or SQL
 * table — e.g., aggregated search results, cross-model projections, or
 * external API-backed data.
 *
 * Register via ResolverMapBuilder::custom():
 *
 *   $map->custom($container->getEntitySet('SearchItems'), SearchItemsResolver::class);
 */
final readonly class CustomBinding implements ResolverBindingInterface
{
    /**
     * @param class-string<EntitySetResolverInterface> $resolverClass
     */
    public function __construct(public string $resolverClass) {}

    public function createResolver(): EntitySetResolverInterface
    {
        return app($this->resolverClass);
    }
}
