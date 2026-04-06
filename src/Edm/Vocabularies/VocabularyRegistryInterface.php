<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Vocabularies;

/**
 * Runtime registry of known vocabulary namespaces and their aliases.
 *
 * This interface serves a single purpose at runtime: resolving alias-
 * qualified term names to their fully qualified form so that
 * AnnotatableInterface::getAnnotation() can accept both forms
 * transparently.
 *
 * The registry is populated once at application boot from the static
 * catalog. It has no knowledge of Term definitions, type shapes, or
 * generator concerns — those belong to VocabularyCatalogInterface.
 *
 * Example:
 *
 *   $registry->resolveAlias('UI', 'LineItem');
 *   // → "com.sap.vocabularies.UI.v1.LineItem"
 *
 *   $registry->resolveAlias('Core', 'Description');
 *   // → "Org.OData.Core.V1.Description"
 *
 *   $registry->fullyQualify('UI.LineItem');
 *   // → "com.sap.vocabularies.UI.v1.LineItem"
 *
 *   $registry->fullyQualify('com.sap.vocabularies.UI.v1.LineItem');
 *   // → "com.sap.vocabularies.UI.v1.LineItem"  (identity, already qualified)
 */
interface VocabularyRegistryInterface
{
    /**
     * Resolves an alias and a simple term name to the fully qualified
     * term name.
     *
     * Returns null when the alias is unknown to this registry. Does not
     * validate whether the term actually exists in the vocabulary —
     * that is the generator's concern.
     */
    public function resolveAlias(string $alias, string $termName): ?string;

    /**
     * Takes a term name that is either alias-qualified ("UI.LineItem")
     * or already fully qualified ("com.sap.vocabularies.UI.v1.LineItem")
     * and returns the fully qualified form.
     *
     * Returns null when the input contains an alias that is unknown to
     * this registry. Returns the input unchanged when it is already
     * fully qualified.
     */
    public function fullyQualify(string $term): ?string;

    /**
     * Returns all registered alias-to-namespace mappings.
     *
     * The key is the alias (e.g. "UI"), the value is the full namespace
     * (e.g. "com.sap.vocabularies.UI.v1"). Useful for serialising
     * Reference and Include elements in a metadata document.
     *
     * @return array<string, string>
     */
    public function getAliasMap(): array;

    /**
     * Whether this registry knows the given alias.
     */
    public function hasAlias(string $alias): bool;
}