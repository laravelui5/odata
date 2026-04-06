<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Builder;

use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\EdmxInterface;
use LaravelUi5\OData\Service\Contracts\EntitySetResolverInterface;
use LaravelUi5\OData\Service\Contracts\EntitySetSourceInterface;
use LaravelUi5\OData\Service\Contracts\ResolverBindingInterface;
use LaravelUi5\OData\Service\Resolver\CustomBinding;
use LaravelUi5\OData\Service\Resolver\EloquentBinding;
use LaravelUi5\OData\Service\Resolver\ResolverMap;
use LaravelUi5\OData\Service\Resolver\SqlBinding;
use LaravelUi5\OData\Service\Resolver\SqlSourceBinding;

/**
 * Fluent builder for constructing a ResolverMap.
 *
 * Used in ODataService::registerBindings() to declare entity set bindings.
 * Accepts EntitySetInterface as keys (type-safe), stores by name for
 * serialization.
 */
final class ResolverMapBuilder
{
    /** @var array<string, ResolverBindingInterface> entitySetName => binding */
    private array $bindings = [];

    public function __construct(private readonly EdmxInterface $edmx) {}

    public function getEdmx(): EdmxInterface
    {
        return $this->edmx;
    }

    /**
     * Bind an entity set to an Eloquent model class.
     *
     * @param class-string<\Illuminate\Database\Eloquent\Model> $modelClass
     */
    public function eloquent(EntitySetInterface $set, string $modelClass): static
    {
        $this->bindings[$set->getName()] = new EloquentBinding($modelClass);
        return $this;
    }

    /**
     * Bind an entity set to a raw database table or view.
     */
    public function sql(EntitySetInterface $set, string $table, ?string $connection = null): static
    {
        $this->bindings[$set->getName()] = new SqlBinding($table, $connection);
        return $this;
    }

    /**
     * Bind an entity set to an EntitySetSourceInterface implementation.
     *
     * The source class is resolved from the Laravel container at runtime,
     * so dependencies (tenant context, user, etc.) are injected.
     *
     * @param class-string<EntitySetSourceInterface> $sourceClass
     */
    public function sqlSource(EntitySetInterface $set, string $sourceClass): static
    {
        $this->bindings[$set->getName()] = new SqlSourceBinding($sourceClass);
        return $this;
    }

    /**
     * Bind an entity set to a custom EntitySetResolverInterface implementation.
     *
     * The resolver class is resolved from the Laravel container at runtime,
     * so dependencies are injected. Use this for entity sets that don't map
     * to a single Eloquent model or SQL table.
     *
     * @param class-string<EntitySetResolverInterface> $resolverClass
     */
    public function custom(EntitySetInterface $set, string $resolverClass): static
    {
        $this->bindings[$set->getName()] = new CustomBinding($resolverClass);
        return $this;
    }

    /**
     * Build the frozen ResolverMap from collected bindings.
     */
    public function build(): ResolverMap
    {
        return new ResolverMap($this->bindings);
    }

    /**
     * @return array<string, ResolverBindingInterface>
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }
}
