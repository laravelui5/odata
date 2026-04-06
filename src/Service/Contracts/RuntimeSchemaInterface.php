<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Contracts;

use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\FunctionImportInterface;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Edm\Contracts\EdmxInterface;

/**
 * The frozen runtime schema — an EdmxInterface paired with its resolver map.
 *
 * Produced by RuntimeSchemaBuilderInterface::build() and cached for the
 * lifetime of the request. The Engine queries it to obtain the resolver for
 * a given entity set; it never touches the schema again after query planning.
 *
 * The resolver map is keyed by EntitySetInterface object identity
 * (spl_object_id). Since EdmxInterface is frozen, getEntitySet() always
 * returns the same instance, making object identity a correct and stable key.
 */
interface RuntimeSchemaInterface
{
    /**
     * The frozen Edm document this runtime schema was built from.
     */
    public function getEdmx(): EdmxInterface;

    /**
     * Returns the resolver bound to the given entity set.
     *
     * The $set parameter must be the exact EntitySetInterface instance from
     * this schema's EdmxInterface — not a different instance with the same
     * name. Use getEdmx()->getEntityContainer()->getEntitySet($name) to
     * obtain the canonical instance.
     *
     * @throws \RuntimeException if no resolver was bound for $set
     */
    public function getResolver(EntitySetInterface $set): EntitySetResolverInterface;

    /**
     * Returns the resolver bound to the given function import.
     *
     * @throws \RuntimeException if no resolver was bound for $import
     */
    public function getFunctionResolver(FunctionImportInterface $import): FunctionResolverInterface;

    /**
     * Returns the resolver bound to the given singleton.
     *
     * @throws \RuntimeException if no resolver was bound for $singleton
     */
    public function getSingletonResolver(SingletonInterface $singleton): SingletonResolverInterface;
}
