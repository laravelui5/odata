<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Builder;

use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\FunctionImportInterface;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Edm\Contracts\EdmxInterface;
use LaravelUi5\OData\Driver\Sql\EloquentEntitySetResolver;
use LaravelUi5\OData\Service\Contracts\EntitySetResolverInterface;
use LaravelUi5\OData\Service\Contracts\FunctionResolverInterface;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaBuilderInterface;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaInterface;
use LaravelUi5\OData\Service\Contracts\SingletonResolverInterface;
use LaravelUi5\OData\Service\RuntimeSchema;

/**
 * Mutable accumulator that binds resolvers to a frozen EdmxInterface (Stage 2).
 *
 * Retrieve canonical EntitySetInterface and FunctionImportInterface instances
 * via getEdmx() before binding — object identity (spl_object_id) is the map key.
 *
 * Example:
 *   $c = $builder->getEdmx()->getEntityContainer();
 *   $builder->bindEntitySet($c->getEntitySet('Partners'), new EloquentEntitySetResolver(Partner::class));
 */
final class RuntimeSchemaBuilder implements RuntimeSchemaBuilderInterface
{
    private bool $built = false;

    /**
     * @var array<int, EntitySetResolverInterface>  keyed by spl_object_id of EntitySetInterface
     */
    private array $entitySetResolvers = [];

    /**
     * @var array<int, FunctionResolverInterface>  keyed by spl_object_id of FunctionImportInterface
     */
    private array $functionResolvers = [];

    /**
     * @var array<int, SingletonResolverInterface>  keyed by spl_object_id of SingletonInterface
     */
    private array $singletonResolvers = [];

    public function __construct(private readonly EdmxInterface $edmx) {}

    public function getEdmx(): EdmxInterface
    {
        return $this->edmx;
    }

    public function bindEntitySet(
        EntitySetInterface        $set,
        EntitySetResolverInterface $resolver,
    ): static {
        $this->assertNotBuilt();
        $this->assertEntitySetBelongs($set);

        $this->entitySetResolvers[spl_object_id($set)] = $resolver;
        return $this;
    }

    public function bindFunctionImport(
        FunctionImportInterface   $import,
        FunctionResolverInterface $resolver,
    ): static {
        $this->assertNotBuilt();
        $this->assertFunctionImportBelongs($import);

        $this->functionResolvers[spl_object_id($import)] = $resolver;
        return $this;
    }

    public function bindSingleton(
        SingletonInterface         $singleton,
        SingletonResolverInterface $resolver,
    ): static {
        $this->assertNotBuilt();
        $this->assertSingletonBelongs($singleton);

        $this->singletonResolvers[spl_object_id($singleton)] = $resolver;
        return $this;
    }

    public function build(): RuntimeSchemaInterface
    {
        $this->assertNotBuilt();
        $this->assertAllEntitySetsBound();
        $this->built = true;

        $schema = new RuntimeSchema(
            edmx:               $this->edmx,
            resolvers:          $this->entitySetResolvers,
            functionResolvers:  $this->functionResolvers,
            singletonResolvers: $this->singletonResolvers,
        );

        // Wire schema reference into Eloquent resolvers so they can
        // delegate virtual expand resolution to custom resolvers.
        foreach ($this->entitySetResolvers as $resolver) {
            if ($resolver instanceof EloquentEntitySetResolver) {
                $resolver->setSchema($schema);
            }
        }

        return $schema;
    }

    // ── Guards ─────────────────────────────────────────────────────────────────

    private function assertNotBuilt(): void
    {
        if ($this->built) {
            throw new \LogicException('RuntimeSchemaBuilder has already been built and must not be mutated.');
        }
    }

    private function assertEntitySetBelongs(EntitySetInterface $set): void
    {
        $id = spl_object_id($set);
        foreach ($this->edmx->getEntityContainer()->getEntitySets() as $known) {
            if (spl_object_id($known) === $id) {
                return;
            }
        }
        throw new \InvalidArgumentException(
            sprintf('EntitySet "%s" is not part of this builder\'s EdmxInterface.', $set->getName())
        );
    }

    private function assertFunctionImportBelongs(FunctionImportInterface $import): void
    {
        $id = spl_object_id($import);
        foreach ($this->edmx->getEntityContainer()->getFunctionImports() as $known) {
            if (spl_object_id($known) === $id) {
                return;
            }
        }
        throw new \InvalidArgumentException(
            sprintf('FunctionImport "%s" is not part of this builder\'s EdmxInterface.', $import->getName())
        );
    }

    private function assertSingletonBelongs(SingletonInterface $singleton): void
    {
        $id = spl_object_id($singleton);
        foreach ($this->edmx->getEntityContainer()->getSingletons() as $known) {
            if (spl_object_id($known) === $id) {
                return;
            }
        }
        throw new \InvalidArgumentException(
            sprintf('Singleton "%s" is not part of this builder\'s EdmxInterface.', $singleton->getName())
        );
    }

    private function assertAllEntitySetsBound(): void
    {
        $unbound = [];
        foreach ($this->edmx->getEntityContainer()->getEntitySets() as $set) {
            if (!isset($this->entitySetResolvers[spl_object_id($set)])) {
                $unbound[] = $set->getName();
            }
        }

        if ($unbound !== []) {
            throw new \RuntimeException(
                sprintf(
                    'The following entity sets have no resolver bound: %s.',
                    implode(', ', $unbound)
                )
            );
        }
    }
}
