<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Console;

use DOMElement;
use DOMXPath;
use LaravelUi5\OData\Edm\Vocabularies\VocabularyEntryInterface;

/**
 * The types of all loaded vocabularies, keyed by qualified name.
 *
 * A vocabulary refers to a type by alias (`UI.DataFieldAbstract`, `Core.Tag`); the alias is local to the document
 * that declares it in an `edmx:Include`. The model resolves every reference against the document it stands in, so
 * each type, base type and property type is held as `Namespace.Name` (`com.sap.vocabularies.UI.v1.DataFieldAbstract`).
 * Simple names are not unique across vocabularies: `FilterExpressionType` is an enum type in Common and a type
 * definition in Capabilities.
 *
 * Load every vocabulary before asking: a base type may come from another vocabulary
 * (`Aggregation.NavigationPropertyAggregationCapabilities` derives from `Capabilities.NavigationPropertyRestriction`).
 *
 * @internal Used by {@see VocabularyGenerator}; not part of the package API.
 *
 * @see OData CSDL XML v4.01 §3.3 (Alias), §9.1.2 (Derived Complex Type), §9.1.3 (Abstract Complex Type)
 */
final class VocabularyTypeModel
{
    private const string EDM_NAMESPACE = 'http://docs.oasis-open.org/odata/ns/edm';

    private const string EDMX_NAMESPACE = 'http://docs.oasis-open.org/odata/ns/edmx';

    /** @var array<string, array{alias: string, phpNamespace: string, aliases: array<string, string>}> by namespace */
    private array $schemas = [];

    /** @var array<string, string> qualified name → qualified underlying type */
    private array $typeDefinitions = [];

    /**
     * @var array<string, array{name: string, namespace: string, isFlags: bool, members: list<array{name: string, value: int}>}>
     */
    private array $enumTypes = [];

    /**
     * @var array<string, array{name: string, namespace: string, baseType: string|null, abstract: bool, properties: list<array{name: string, type: string, nullable: bool, required: bool}>}>
     */
    private array $complexTypes = [];

    /** @var array<string, array<string, string>> qualified complex type → property → its `Core.Description` */
    private array $propertyDescriptions = [];

    /**
     * Reads the schema of one vocabulary document: its alias map, type definitions, enum types and complex types.
     *
     * Returns the schema's namespace, or null when the document holds no `Schema`.
     */
    public function load(VocabularyEntryInterface $entry, DOMXPath $xpath): ?string
    {
        $xpath->registerNamespace('edm', self::EDM_NAMESPACE);
        $xpath->registerNamespace('edmx', self::EDMX_NAMESPACE);

        $schema = $xpath->query('//edm:Schema')->item(0);
        if (!$schema instanceof DOMElement) {
            return null;
        }
        $namespace = $schema->getAttribute('Namespace');
        $alias     = $schema->getAttribute('Alias');

        $aliases = [];
        foreach ($xpath->query('//edmx:Reference/edmx:Include') as $include) {
            if ($include instanceof DOMElement && $include->getAttribute('Alias') !== '') {
                $aliases[$include->getAttribute('Alias')] = $include->getAttribute('Namespace');
            }
        }
        if ($alias !== '') {
            $aliases[$alias] = $namespace;
        }

        $this->schemas[$namespace] = [
            'alias'        => $alias,
            'phpNamespace' => $entry->getPhpNamespace(),
            'aliases'      => $aliases,
        ];

        foreach ($xpath->query('edm:TypeDefinition', $schema) as $node) {
            if ($node instanceof DOMElement && $node->getAttribute('Name') !== '') {
                $underlying = $node->getAttribute('UnderlyingType') ?: 'Edm.String';
                $this->typeDefinitions[$namespace . '.' . $node->getAttribute('Name')]
                    = $this->qualify($underlying, $namespace);
            }
        }

        foreach ($xpath->query('edm:EnumType', $schema) as $node) {
            if (!$node instanceof DOMElement || $node->getAttribute('Name') === '') {
                continue;
            }
            $members = [];
            foreach ($xpath->query('edm:Member', $node) as $member) {
                if ($member instanceof DOMElement && $member->getAttribute('Name') !== '') {
                    $value     = $member->getAttribute('Value');
                    $members[] = [
                        'name'  => $member->getAttribute('Name'),
                        'value' => $value !== '' ? (int) $value : count($members),
                    ];
                }
            }
            $this->enumTypes[$namespace . '.' . $node->getAttribute('Name')] = [
                'name'      => $node->getAttribute('Name'),
                'namespace' => $namespace,
                'isFlags'   => $node->getAttribute('IsFlags') === 'true',
                'members'   => $members,
            ];
        }

        foreach ($xpath->query('edm:ComplexType', $schema) as $node) {
            if (!$node instanceof DOMElement || $node->getAttribute('Name') === '') {
                continue;
            }
            $properties = [];
            $qualified  = $namespace . '.' . $node->getAttribute('Name');
            foreach ($xpath->query('edm:Property', $node) as $property) {
                if ($property instanceof DOMElement) {
                    $description = $this->description($property, $xpath, $namespace);
                    if ($description !== '') {
                        $this->propertyDescriptions[$qualified][$property->getAttribute('Name')] = $description;
                    }
                    $type     = $this->qualify($property->getAttribute('Type'), $namespace);
                    $nullable = strtolower($property->getAttribute('Nullable') ?: 'true') !== 'false';
                    $properties[] = [
                        'name'     => $property->getAttribute('Name'),
                        'type'     => $type,
                        'nullable' => $nullable,
                        // A record must give a value only for a single-valued property that is neither
                        // nullable nor has a default; an absent collection is an empty one (CSDL XML 4.01 §14.4.10).
                        'required' => !$nullable && !$property->hasAttribute('DefaultValue')
                            && !str_starts_with($type, 'Collection('),
                    ];
                }
            }
            $baseType = $node->getAttribute('BaseType');
            $this->complexTypes[$qualified] = [
                'name'       => $node->getAttribute('Name'),
                'namespace'  => $namespace,
                'baseType'   => $baseType !== '' ? $this->qualify($baseType, $namespace) : null,
                'abstract'   => $node->getAttribute('Abstract') === 'true',
                'properties' => $properties,
            ];
        }

        return $namespace;
    }

    /**
     * A type reference as it stands in the document of `$contextNamespace`, with its alias replaced by the namespace
     * the document's `edmx:Include` gives it (`UI.DataField` → `com.sap.vocabularies.UI.v1.DataField`).
     * `Collection(…)` keeps its wrapper; `Edm.*` and an unknown prefix stay as they are.
     */
    public function qualify(string $type, string $contextNamespace): string
    {
        if (str_starts_with($type, 'Collection(') && str_ends_with($type, ')')) {
            return 'Collection(' . $this->qualify(substr($type, 11, -1), $contextNamespace) . ')';
        }
        $dot = strrpos($type, '.');
        if ($dot === false || str_starts_with($type, 'Edm.')) {
            return $type;
        }
        $prefix    = substr($type, 0, $dot);
        $namespace = $this->schemas[$contextNamespace]['aliases'][$prefix] ?? null;

        return $namespace !== null ? $namespace . substr($type, $dot) : $type;
    }

    /** The PHP namespace generated for the vocabulary that declares `$qualifiedName`, or null when it is not loaded. */
    public function phpNamespaceOf(string $qualifiedName): ?string
    {
        return $this->schemas[self::namespaceOf($qualifiedName)]['phpNamespace'] ?? null;
    }

    public function isComplexType(string $qualifiedName): bool
    {
        return isset($this->complexTypes[$qualifiedName]);
    }

    public function isAbstract(string $qualifiedName): bool
    {
        return $this->complexTypes[$qualifiedName]['abstract'] ?? false;
    }

    public function baseType(string $qualifiedName): ?string
    {
        return $this->complexTypes[$qualifiedName]['baseType'] ?? null;
    }

    /**
     * The base types of a complex type, nearest first. Stops at a base type that is not loaded and at a cycle.
     *
     * @return list<string>
     */
    public function ancestors(string $qualifiedName): array
    {
        $ancestors = [];
        $current   = $this->baseType($qualifiedName);
        while ($current !== null && $this->isComplexType($current) && !in_array($current, $ancestors, true)
            && $current !== $qualifiedName) {
            $ancestors[] = $current;
            $current     = $this->baseType($current);
        }

        return $ancestors;
    }

    /**
     * The properties a complex type declares itself.
     *
     * @return list<array{name: string, type: string, nullable: bool, required: bool}>
     */
    public function ownProperties(string $qualifiedName): array
    {
        return $this->complexTypes[$qualifiedName]['properties'] ?? [];
    }

    /**
     * All properties of a complex type: those of the root base type first, its own last.
     *
     * A derived type may declare an inherited property again to narrow it (`UI.DataFieldWithUrl` redeclares `Value`,
     * `Edm.Untyped` in `UI.DataField`, as `Edm.PrimitiveType`). The redeclaration replaces the inherited property in
     * its place, so each name occurs once, with the type and nullability of the most derived declaration.
     *
     * @return list<array{name: string, type: string, nullable: bool, required: bool}>
     */
    public function properties(string $qualifiedName): array
    {
        $properties = [];
        foreach ([...array_reverse($this->ancestors($qualifiedName)), $qualifiedName] as $type) {
            foreach ($this->ownProperties($type) as $property) {
                $properties[$property['name']] = $property;
            }
        }

        return array_values($properties);
    }

    /**
     * The `Core.Description` of a property of `$qualifiedName`, from the most derived type that describes it
     * (a redeclaration may say more than the base); '' when none does.
     */
    public function propertyDescription(string $qualifiedName, string $property): string
    {
        foreach ([$qualifiedName, ...$this->ancestors($qualifiedName)] as $type) {
            if (isset($this->propertyDescriptions[$type][$property])) {
                return $this->propertyDescriptions[$type][$property];
            }
        }

        return '';
    }

    /**
     * The type that first declares a property of `$qualifiedName`, itself or a base type; null when none does.
     */
    public function declaringType(string $qualifiedName, string $property): ?string
    {
        $declaring = null;
        foreach ([$qualifiedName, ...$this->ancestors($qualifiedName)] as $type) {
            if (in_array($property, array_column($this->ownProperties($type), 'name'), true)) {
                $declaring = $type;
            }
        }

        return $declaring;
    }

    /**
     * Every loaded complex type deriving from `$qualifiedName`, directly or further down, in load order.
     *
     * @return list<string>
     */
    public function derivedTypes(string $qualifiedName): array
    {
        $derived = [];
        foreach (array_keys($this->complexTypes) as $type) {
            if ($type !== $qualifiedName && in_array($qualifiedName, $this->ancestors($type), true)) {
                $derived[] = $type;
            }
        }

        return $derived;
    }

    public function isEnumType(string $qualifiedName): bool
    {
        return isset($this->enumTypes[$qualifiedName]);
    }

    /**
     * @return array{name: string, namespace: string, isFlags: bool, members: list<array{name: string, value: int}>}|null
     */
    public function enumType(string $qualifiedName): ?array
    {
        return $this->enumTypes[$qualifiedName] ?? null;
    }

    /** A type definition resolved to its underlying type, through any chain; every other type as it is. */
    public function resolve(string $qualifiedName): string
    {
        $seen = [];
        while (isset($this->typeDefinitions[$qualifiedName]) && !isset($seen[$qualifiedName])) {
            $seen[$qualifiedName] = true;
            $qualifiedName        = $this->typeDefinitions[$qualifiedName];
        }

        return $qualifiedName;
    }

    /**
     * References the loaded vocabularies cannot resolve: base types and property types that are neither `Edm.*` nor
     * a loaded type (`com.sap.vocabularies.Hierarchy.v1.HierarchyType`, whose vocabulary the catalog does not carry).
     *
     * @return list<string> one line per reference, `Referrer → Type`
     */
    public function unresolvedReferences(): array
    {
        $unresolved = [];
        foreach ($this->complexTypes as $name => $type) {
            if ($type['baseType'] !== null && !$this->isKnown($type['baseType'])) {
                $unresolved[] = "{$name} → {$type['baseType']}";
            }
            foreach ($type['properties'] as $property) {
                if (!$this->isKnown($property['type'])) {
                    $unresolved[] = "{$name}/{$property['name']} → {$property['type']}";
                }
            }
        }
        foreach ($this->typeDefinitions as $name => $underlying) {
            if (!$this->isKnown($underlying)) {
                $unresolved[] = "{$name} → {$underlying}";
            }
        }

        return $unresolved;
    }

    /** The namespace part of a qualified name (`Org.OData.Core.V1.Tag` → `Org.OData.Core.V1`). */
    public static function namespaceOf(string $qualifiedName): string
    {
        $dot = strrpos($qualifiedName, '.');

        return $dot === false ? '' : substr($qualifiedName, 0, $dot);
    }

    /** The simple name of a qualified name (`Org.OData.Core.V1.Tag` → `Tag`). */
    public static function simpleName(string $qualifiedName): string
    {
        $dot = strrpos($qualifiedName, '.');

        return $dot === false ? $qualifiedName : substr($qualifiedName, $dot + 1);
    }

    /** The `Core.Description` annotated on an element, as one line; '' when it has none. */
    private function description(DOMElement $element, DOMXPath $xpath, string $namespace): string
    {
        foreach ($xpath->query('edm:Annotation', $element) as $annotation) {
            if (!$annotation instanceof DOMElement
                || $this->qualify($annotation->getAttribute('Term'), $namespace) !== 'Org.OData.Core.V1.Description') {
                continue;
            }
            $text = $annotation->hasAttribute('String')
                ? $annotation->getAttribute('String')
                : (string) $xpath->query('edm:String', $annotation)->item(0)?->textContent;

            return trim((string) preg_replace('/\s+/', ' ', $text));
        }

        return '';
    }

    private function isKnown(string $type): bool
    {
        if (str_starts_with($type, 'Collection(') && str_ends_with($type, ')')) {
            $type = substr($type, 11, -1);
        }

        return str_starts_with($type, 'Edm.')
            || isset($this->complexTypes[$type])
            || isset($this->enumTypes[$type])
            || isset($this->typeDefinitions[$type]);
    }
}
