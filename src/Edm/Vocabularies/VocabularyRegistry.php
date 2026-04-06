<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Vocabularies;

/**
 * Runtime registry that resolves alias-qualified OData term names to their
 * fully qualified form.
 *
 * The registry is initialised once from a VocabularyCatalogInterface and is
 * immutable thereafter. The alias map is built eagerly in the constructor.
 *
 * The static singleton is lazily initialised from VocabularyCatalog::default()
 * on first call to getInstance(). Annotatable model objects use the singleton
 * to resolve aliases in getAnnotation() without carrying a registry reference
 * in their constructors.
 *
 * NOTE: This class is not declared readonly because the PHP readonly-class
 * modifier makes ALL typed properties readonly, which includes static
 * properties and prevents the mutable static $instance field required for
 * the lazy-singleton pattern. Instance immutability is preserved by
 * declaring every instance property readonly explicitly.
 *
 * @see VocabularyRegistryInterface
 * @see OData CSDL XML v4.01 §4.1 (Namespace and Alias)
 */
final class VocabularyRegistry implements VocabularyRegistryInterface
{
    /** Lazily initialised singleton, populated on first call to getInstance(). */
    private static ?self $instance = null;

    /** @var array<string, string> alias => namespace */
    private readonly array $aliasMap;

    /** @var list<string> all known namespaces, for already-qualified detection */
    private readonly array $namespaces;

    public function __construct(VocabularyCatalogInterface $catalog)
    {
        $aliasMap   = [];
        $namespaces = [];
        foreach ($catalog->getEntries() as $entry) {
            $aliasMap[$entry->getAlias()] = $entry->getNamespace();
            $namespaces[]                 = $entry->getNamespace();
        }
        $this->aliasMap   = $aliasMap;
        $this->namespaces = $namespaces;
    }

    /**
     * Returns the shared singleton instance, lazily initialised from
     * VocabularyCatalog::default() on first call.
     */
    public static function getInstance(): self
    {
        return self::$instance ??= new self(VocabularyCatalog::default());
    }

    public function resolveAlias(string $alias, string $termName): ?string
    {
        if (!isset($this->aliasMap[$alias])) {
            return null;
        }
        return $this->aliasMap[$alias] . '.' . $termName;
    }

    public function fullyQualify(string $term): ?string
    {
        // Already fully qualified when its prefix matches a known namespace.
        foreach ($this->namespaces as $ns) {
            if (str_starts_with($term, $ns . '.')) {
                return $term;
            }
        }

        // Alias-qualified: split on the first dot.
        $dotPos = strpos($term, '.');
        if ($dotPos === false) {
            return null;
        }
        $alias     = substr($term, 0, $dotPos);
        $remainder = substr($term, $dotPos + 1);

        if (!isset($this->aliasMap[$alias])) {
            return null;
        }

        return $this->aliasMap[$alias] . '.' . $remainder;
    }

    /** @return array<string, string> */
    public function getAliasMap(): array
    {
        return $this->aliasMap;
    }

    public function hasAlias(string $alias): bool
    {
        return isset($this->aliasMap[$alias]);
    }
}
