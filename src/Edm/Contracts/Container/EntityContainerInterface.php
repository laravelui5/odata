<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Container;

use LaravelUi5\OData\Edm\Contracts\AnnotatableInterface;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\NamedElementInterface;

/**
 * The entity container — the single runtime context that groups all
 * addressable resources of an OData service.
 *
 * Every OData service exposes exactly one entity container in its
 * metadata document. The container holds entity sets, singletons, and
 * function imports. A container may extend another container defined
 * in a referenced schema, inheriting its members.
 *
 * The container is the root from which a query planner resolves all
 * resource addresses. Navigation property bindings on entity sets and
 * singletons are resolved within the scope of this container.
 *
 * @see OData CSDL XML v4.01 §13 (Entity Container)
 */
interface EntityContainerInterface extends NamedElementInterface, AnnotatableInterface, AnnotationTargetInterface
{
    /**
     * All entity sets in this container.
     *
     * @return list<EntitySetInterface>
     */
    public function getEntitySets(): array;

    /**
     * Returns the entity set with the given simple name, or null
     * when not found.
     */
    public function getEntitySet(string $name): ?EntitySetInterface;

    /**
     * All singletons in this container.
     *
     * @return list<SingletonInterface>
     */
    public function getSingletons(): array;

    /**
     * Returns the singleton with the given simple name, or null
     * when not found.
     */
    public function getSingleton(string $name): ?SingletonInterface;

    /**
     * All function imports in this container.
     *
     * @return list<FunctionImportInterface>
     */
    public function getFunctionImports(): array;

    /**
     * Returns the function import with the given simple name, or null
     * when not found.
     *
     * When multiple overloads of the same function are imported under
     * different names, each import is retrieved by its own name.
     */
    public function getFunctionImport(string $name): ?FunctionImportInterface;

    /**
     * The qualified name of the container this container extends,
     * or null when this is a root container.
     *
     * Extension inheritance is resolved by the schema builder; this
     * interface reflects only the declared extension target name.
     *
     * @see OData CSDL XML v4.01 §13.1
     */
    public function getExtendsName(): ?string;
}
