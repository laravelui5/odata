<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Resolver;

use LaravelUi5\OData\Service\Contracts\ResolverBindingInterface;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaBuilderInterface;

/**
 * Frozen, serializable registry of entity set resolver bindings.
 *
 * On both cold and warm boot, the ResolverMap drives resolver creation:
 * it iterates its bindings, calls createResolver() on each, and binds the
 * result to the corresponding entity set in the RuntimeSchemaBuilder.
 */
final readonly class ResolverMap
{
    /**
     * @param array<string, ResolverBindingInterface> $bindings entitySetName => binding
     */
    public function __construct(private array $bindings) {}

    /**
     * Create resolvers and bind them to entity sets in the runtime builder.
     */
    public function applyTo(RuntimeSchemaBuilderInterface $builder): void
    {
        $container = $builder->getEdmx()->getEntityContainer();

        foreach ($this->bindings as $entitySetName => $binding) {
            $set = $container->getEntitySet($entitySetName);
            if ($set !== null) {
                $builder->bindEntitySet($set, $binding->createResolver());
            }
        }
    }

    /**
     * @return array<string, ResolverBindingInterface>
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }
}
