<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Contracts;

use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\FunctionImportInterface;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EnumTypeInterface;
use LaravelUi5\OData\Edm\Contracts\EdmxInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\ReferenceInterface;
use LaravelUi5\OData\Edm\Vocabularies\Vocabulary;
use LaravelUi5\OData\Edm\Contracts\Type\ComplexTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeDefinitionInterface;

/**
 * Mutable accumulator that produces a frozen EdmxInterface.
 *
 * This is Stage 1 of the two-stage builder. It holds only Edm structure —
 * no resolvers, no runtime state. The result of build() is safe to cache
 * to a generated PHP file by odata:cache and reloaded by EdmxLoader.
 *
 * ODataService subclasses return an instance of this interface from
 * discover() and populate it with their entity types, functions, and
 * container members.
 *
 * @see RuntimeSchemaBuilderInterface  Stage 2 — binds resolvers to the EdmxInterface
 */
interface EdmBuilderInterface
{
    // ── Schema identity ───────────────────────────────────────────────────────

    /**
     * Sets the namespace of the schema, e.g. "MyService.Data".
     */
    public function namespace(string $namespace): static;

    /**
     * Sets the optional alias for the schema namespace, e.g. "self".
     */
    public function alias(string $alias): static;

    /**
     * Sets the name of the entity container.
     *
     * Must be set explicitly — the concrete EdmBuilder defaults to
     * "DefaultContainer" when this is not called, matching the behaviour
     * of the legacy implementation.
     */
    public function containerName(string $name): static;

    // ── References ────────────────────────────────────────────────────────────

    public function addReference(ReferenceInterface $reference): static;

    /**
     * Declare a built-in vocabulary reference by enum.
     *
     * Shorthand for looking up the vocabulary in the default catalog
     * and calling addReference() with the resulting Reference object.
     */
    public function useVocabulary(Vocabulary $vocabulary): static;

    // ── Types ─────────────────────────────────────────────────────────────────

    public function addEntityType(EntityTypeInterface $type): static;

    public function addComplexType(ComplexTypeInterface $type): static;

    public function addEnumType(EnumTypeInterface $type): static;

    public function addTypeDefinition(TypeDefinitionInterface $type): static;

    public function addFunction(FunctionInterface $function): static;

    // ── Container members (no resolvers at this stage) ────────────────────────

    /**
     * Adds an entity set to the container.
     *
     * No resolver is registered here — resolver binding is Stage 2 and
     * happens exclusively in RuntimeSchemaBuilderInterface.
     */
    public function addEntitySet(EntitySetInterface $set): static;

    /**
     * Inject a navigation property into an already-added entity type and set.
     *
     * Used by virtual expand wiring to add navigation properties to entity
     * types that were already registered (by discovery or manual configure()).
     */
    public function injectNavigationProperty(
        string                      $entityTypeName,
        NavigationPropertyInterface $navProperty,
        string                      $targetEntitySetName,
    ): static;

    /**
     * Adds a singleton to the container.
     *
     * Singletons carry their value as part of the Edm structure; no separate
     * resolver binding is required in the read-only engine.
     */
    public function addSingleton(SingletonInterface $singleton): static;

    /**
     * Adds a function import to the container.
     *
     * No resolver is registered here — resolver binding is Stage 2.
     */
    public function addFunctionImport(FunctionImportInterface $import): static;

    // ── Produce the frozen model ──────────────────────────────────────────────

    /**
     * Freeze the accumulated state into an immutable EdmxInterface.
     *
     * After this call the builder's state must not be mutated; implementations
     * should enforce this by marking themselves as built.
     */
    public function build(): EdmxInterface;
}
