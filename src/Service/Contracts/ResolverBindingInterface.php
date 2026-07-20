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

    /**
     * The **authored** class-string that backs this entity set — the class a consumer
     * reflects on to read class attributes (permissions, capabilities, annotations).
     *
     * This is the source the developer wrote, NOT necessarily the runtime resolver:
     * for a model-backed set it is the Eloquent **model** (the resolver is the generic
     * EloquentEntitySetResolver); for a custom or source-backed set it is that class.
     * Returns `null` when there is no authored class to reflect on — e.g. a raw
     * table/view binding.
     *
     * @return class-string|null
     */
    public function getSourceClass(): ?string;
}
