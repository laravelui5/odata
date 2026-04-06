<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts;

/**
 * Base contract for every named element in the Entity Data Model.
 *
 * Every construct in CSDL — types, properties, functions, container
 * members — carries a simple identifier and belongs to a namespace
 * through its parent schema. This interface makes that visible.
 *
 * @see OData CSDL XML v4.01 §15.2 (Simple Identifier)
 */
interface NamedElementInterface
{
    /**
     * The unqualified name of this element within its parent scope.
     *
     * Must conform to the OData SimpleIdentifier production rule:
     * a non-empty string of at most 128 characters, starting with
     * a letter or underscore, followed by letters, digits, or underscores.
     */
    public function getName(): string;
}
