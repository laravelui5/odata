<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts;

use LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface;

/**
 * The EDMX document — the root of the entire CSDL model.
 *
 * An EDMX document wraps one or more schemas and the single entity
 * container that defines the service's runtime resources. It is the
 * entry point for any consumer of the model, whether that is a
 * serialiser producing XML or JSON metadata, a query planner building
 * a query plan from a parsed OData URL, or a driver mapping the model
 * to a database schema.
 *
 * In the fully-resolved model that this interface describes, all cross-
 * schema references have already been resolved to object references by
 * the schema builder. There are no unresolved qualified names at this
 * level.
 *
 * @see OData CSDL XML v4.01 §4 (CSDL XML Document)
 */
interface EdmxInterface
{
    /**
     * The OData version this document conforms to.
     *
     * For documents conforming to this specification the value is
     * "4.0" or "4.01".
     *
     * @see OData CSDL XML v4.01 §4
     */
    public function getVersion(): string;

    /**
     * All external documents referenced by this metadata document,
     * in document order.
     *
     * References record which vocabulary documents were consulted
     * when the model was built. A serialiser emits them as
     * <edmx:Reference> elements so that clients can locate the
     * vocabulary definitions their annotations refer to.
     *
     * @return list<ReferenceInterface>
     * @see OData CSDL XML v4.01 §4.1
     */
    public function getReferences(): array;

    /**
     * Returns the reference for the given URI, or null when no
     * reference with that URI is declared.
     */
    public function getReference(string $uri): ?ReferenceInterface;

    /**
     * All schemas contained in or referenced by this document,
     * keyed by their namespace.
     *
     * @return array<string, SchemaInterface>
     */
    public function getSchemas(): array;

    /**
     * Returns the schema with the given namespace, or null when
     * not found.
     */
    public function getSchema(string $namespace): ?SchemaInterface;

    /**
     * The single entity container of this service.
     *
     * Every well-formed OData service metadata document contains
     * exactly one entity container across all its schemas.
     */
    public function getEntityContainer(): EntityContainerInterface;
}
