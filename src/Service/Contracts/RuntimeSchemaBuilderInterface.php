<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Contracts;

use InvalidArgumentException;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\FunctionImportInterface;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Edm\Contracts\EdmxInterface;
use RuntimeException;

/**
 * Mutable accumulator that binds resolvers to a frozen EdmxInterface and
 * produces a RuntimeSchemaInterface.
 *
 * This is Stage 2 of the two-stage builder. It wraps an already-built
 * EdmxInterface (from either EdmBuilderInterface::build() or EdmxLoader) and
 * accepts resolver registrations for each entity set and function import.
 *
 * ODataService subclasses override bindResolvers() to populate this builder.
 * The method receives the builder already wrapping the correct EdmxInterface,
 * so callers can retrieve canonical entity-set objects via getEdmx() and pass
 * them as object references to bindEntitySet().
 *
 * Typical usage:
 *
 *   protected function bindResolvers(RuntimeSchemaBuilderInterface $builder): RuntimeSchemaBuilderInterface
 *   {
 *       $c = $builder->getEdmx()->getEntityContainer();
 *       return $builder
 *           ->bindEntitySet($c->getEntitySet('Partners'), new EloquentEntitySetResolver(Partner::class));
 *   }
 *
 * @see EdmBuilderInterface  Stage 1 — builds the pure EdmxInterface
 * @see RuntimeSchemaInterface  The frozen result produced by build()
 */
interface RuntimeSchemaBuilderInterface
{
    /**
     * The frozen Edm document this builder was constructed with.
     *
     * Use this to retrieve canonical EntitySetInterface object references for
     * passing to bindEntitySet(). The returned objects have stable identity for
     * the lifetime of this builder.
     */
    public function getEdmx(): EdmxInterface;

    /**
     * Binds a resolver to a specific entity set.
     *
     * The $set parameter must be the exact EntitySetInterface instance from
     * getEdmx() — binding uses object identity (spl_object_id) as the key.
     * Retrieve the canonical instance via:
     *   $builder->getEdmx()->getEntityContainer()->getEntitySet('Name')
     *
     * @throws InvalidArgumentException if $set is not part of this builder's EdmxInterface
     */
    public function bindEntitySet(EntitySetInterface $set, EntitySetResolverInterface $resolver): static;

    /**
     * Binds a resolver to a specific function import.
     *
     * The $import parameter must be the exact FunctionImportInterface instance
     * from getEdmx(). Retrieve it via:
     *   $builder->getEdmx()->getEntityContainer()->getFunctionImport('Name')
     *
     * @throws InvalidArgumentException if $import is not part of this builder's EdmxInterface
     */
    public function bindFunctionImport(FunctionImportInterface $import, FunctionResolverInterface $resolver): static;

    /**
     * Binds a resolver to a specific singleton.
     *
     * @throws InvalidArgumentException if $singleton is not part of this builder's EdmxInterface
     */
    public function bindSingleton(SingletonInterface $singleton, SingletonResolverInterface $resolver): static;

    /**
     * Freeze the accumulated resolver bindings into a RuntimeSchemaInterface.
     *
     * @throws RuntimeException if any entity set in the EdmxInterface has no resolver bound
     */
    public function build(): RuntimeSchemaInterface;
}
