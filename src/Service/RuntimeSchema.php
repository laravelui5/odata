<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service;

use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\FunctionImportInterface;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Edm\Contracts\EdmxInterface;
use LaravelUi5\OData\Service\Contracts\EntitySetResolverInterface;
use LaravelUi5\OData\Service\Contracts\FunctionResolverInterface;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaInterface;
use LaravelUi5\OData\Service\Contracts\SingletonResolverInterface;

/**
 * Frozen runtime schema — an EdmxInterface paired with its resolver maps.
 *
 * Resolver maps are keyed by spl_object_id of each EntitySetInterface /
 * FunctionImportInterface instance. Since EdmxInterface is frozen, the
 * container always returns the same instances, making object identity a
 * correct and stable key for the lifetime of this schema.
 */
final readonly class RuntimeSchema implements RuntimeSchemaInterface
{
    /**
     * @param array<int, EntitySetResolverInterface>   $resolvers           keyed by spl_object_id
     * @param array<int, FunctionResolverInterface>    $functionResolvers   keyed by spl_object_id
     * @param array<int, SingletonResolverInterface>   $singletonResolvers  keyed by spl_object_id
     */
    public function __construct(
        private EdmxInterface $edmx,
        private array         $resolvers,
        private array         $functionResolvers  = [],
        private array         $singletonResolvers = [],
    ) {}

    public function getEdmx(): EdmxInterface
    {
        return $this->edmx;
    }

    public function getResolver(EntitySetInterface $set): EntitySetResolverInterface
    {
        $key = spl_object_id($set);

        return $this->resolvers[$key]
            ?? throw new \RuntimeException(
                sprintf('No resolver bound for entity set "%s".', $set->getName())
            );
    }

    public function getFunctionResolver(FunctionImportInterface $import): FunctionResolverInterface
    {
        $key = spl_object_id($import);

        return $this->functionResolvers[$key]
            ?? throw new \RuntimeException(
                sprintf('No resolver bound for function import "%s".', $import->getName())
            );
    }

    public function getSingletonResolver(SingletonInterface $singleton): SingletonResolverInterface
    {
        $key = spl_object_id($singleton);

        return $this->singletonResolvers[$key]
            ?? throw new \RuntimeException(
                sprintf('No resolver bound for singleton "%s".', $singleton->getName())
            );
    }
}
