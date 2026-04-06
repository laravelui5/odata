<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Resolver;

use LaravelUi5\OData\Driver\Sql\SqlEntitySetResolver;
use LaravelUi5\OData\Service\Contracts\EntitySetResolverInterface;
use LaravelUi5\OData\Service\Contracts\EntitySetSourceInterface;
use LaravelUi5\OData\Service\Contracts\ResolverBindingInterface;

/**
 * Serializable binding for an EntitySetSource-backed entity set.
 *
 * Stores the source class-string and resolves it from the Laravel container
 * at runtime, so dependencies (tenant context, user, etc.) are injected.
 * The source provides the base query with implicit filters; OData query
 * options are applied on top by SqlEntitySetResolver.
 */
final readonly class SqlSourceBinding implements ResolverBindingInterface
{
    /**
     * @param class-string<EntitySetSourceInterface> $sourceClass
     */
    public function __construct(public string $sourceClass) {}

    public function createResolver(): EntitySetResolverInterface
    {
        return new SqlEntitySetResolver(app($this->sourceClass));
    }
}
