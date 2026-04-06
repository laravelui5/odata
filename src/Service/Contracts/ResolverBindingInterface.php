<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Contracts;

/**
 * A serializable binding that knows how to create a resolver at runtime.
 *
 * ResolverBindings are collected during schema configuration and persisted
 * in the ResolverMap. On warm boot, the cached map calls createResolver()
 * to instantiate resolvers without re-running configure() or discovery.
 *
 * Implementations must be serializable as plain PHP (only scalar properties
 * and class-string references — no closures, no object graphs).
 */
interface ResolverBindingInterface
{
    /**
     * Create the entity set resolver for this binding.
     */
    public function createResolver(): EntitySetResolverInterface;
}
