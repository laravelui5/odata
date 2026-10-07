<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Cache;

use Closure;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\CollectionAnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\ConstantAnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\RecordAnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\FunctionImportInterface;
use LaravelUi5\OData\Edm\Contracts\Container\NavigationPropertyBindingInterface;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Edm\Contracts\EdmxInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionParameterInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LaravelUi5\OData\Edm\Contracts\SchemaInterface;
use LaravelUi5\OData\Edm\Contracts\Type\ComplexTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EnumTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeFacetsInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeDefinitionInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeInterface;

/**
 * Generates PHP readonly classes from an EdmxInterface object graph.
 *
 * Output is placed in an Edm/ directory with subdirectories:
 *   Types/    — EntityType and ComplexType classes
 *   Entities/ — EntitySet classes
 *   Enums/    — EnumType classes (future)
 *
 * Each generated class implements the corresponding Edm\Contracts\ interface.
 * The root Edmx.php constructs the full object graph in its constructor.
 *
 * Usage:
 *   $writer = new EdmxWriter($edmx, '/path/to/service/Edm', 'App\\OData\\Edm');
 *   $writer->write();
 */
final class EdmxWriter
{
    public function __construct(
        private readonly EdmxInterface $edmx,
        private readonly string $outputDir,
        private readonly string $namespace,
        private readonly ?Closure $output = null,
    ) {}

    public function write(): void
    {
        $this->ensureDir($this->outputDir);
        $this->ensureDir($this->outputDir . '/Types');
        $this->ensureDir($this->outputDir . '/Entities');

        // Collect all entity type class names for cross-referencing
        $typeMap = $this->buildTypeMap();

        // Write entity types
        foreach ($this->allEntityTypes() as $type) {
            $this->writeEntityType($type, $typeMap);
        }

        // Write complex types
        foreach ($this->allComplexTypes() as $type) {
            $this->writeComplexType($type, $typeMap);
        }

        // Write entity sets
        $container = $this->edmx->getEntityContainer();
        foreach ($container->getEntitySets() as $set) {
            $this->writeEntitySet($set, $typeMap);
        }

        // Write the root Edmx.php that wires everything together
        $this->writeEdmx($typeMap);

        $this->emit('Cache written to ' . $this->outputDir);
    }

    // ── Type map ────────────────────────────────────────────────────────────

    /**
     * Build a map from qualified type name → generated class name.
     *
     * @return array<string, string> qualifiedName → short class name
     */
    private function buildTypeMap(): array
    {
        $map = [];

        foreach ($this->allEntityTypes() as $type) {
            $map[$type->getQualifiedName()] = $type->getName();
        }

        foreach ($this->allComplexTypes() as $type) {
            $map[$type->getQualifiedName()] = $type->getName();
        }

        return $map;
    }

    // ── Entity type generation ──────────────────────────────────────────────

    private function writeEntityType(EntityTypeInterface $type, array $typeMap): void
    {
        $this->writeStructuredType($type, $typeMap, isEntity: true);
    }

    private function writeComplexType(ComplexTypeInterface $type, array $typeMap): void
    {
        $this->writeStructuredType($type, $typeMap, isEntity: false);
    }

    /**
     * Entity and complex types share one shape: a singleton (so circular
     * references between types resolve), navigation properties wired after all
     * types exist, and the base type resolved lazily — the same lookups the cold
     * `EntityType` / `ComplexType` answer, including the fall-back to the base
     * type for key, properties and navigation properties.
     */
    private function writeStructuredType(EntityTypeInterface|ComplexTypeInterface $type, array $typeMap, bool $isEntity): void
    {
        $className = $type->getName();
        $ns        = $this->namespace . '\\Types';
        $interface = $isEntity ? 'EntityTypeInterface' : 'ComplexTypeInterface';

        $props       = $this->generateProperties($type->getDeclaredProperties(), $typeMap);
        $navProps    = $this->generateNavigationProperties($type->getDeclaredNavigationProperties(), $typeMap);
        $annotations = $this->generateAnnotationsCode($type->getAnnotations());
        $baseType    = $type->getBaseType() !== null
            ? '\\' . $this->namespace . '\\Types\\' . ($typeMap[$type->getBaseType()->getQualifiedName()] ?? $type->getBaseType()->getName()) . '::instance()'
            : 'null';

        $entityMembers = '';
        $keyInit       = '';
        if ($isEntity) {
            /** @var EntityTypeInterface $type */
            $keyInit = "        \$this->key = [{$this->generateKeyReferences($type)}];";
            $entityMembers = <<<PHP

                    /** @var list<PropertyInterface> */
                    private array \$key;

                    public function hasStream(): bool { return {$this->bool($type->hasStream())}; }

                    public function getKey(): array
                    {
                        return \$this->key !== [] ? \$this->key : (\$this->getBaseType()?->getKey() ?? []);
                    }

        PHP;
        }

        $code = <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$ns};

        use LaravelUi5\OData\Edm\EdmPrimitiveType;
        use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
        use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
        use LaravelUi5\OData\Edm\Contracts\Type\\{$interface};
        use LaravelUi5\OData\Edm\HasAnnotations;
        use LaravelUi5\OData\Edm\Property\NavigationProperty;
        use LaravelUi5\OData\Edm\Property\Property;
        use LaravelUi5\OData\Edm\Type\PrimitiveType;

        final class {$className} implements {$interface}
        {
            use HasAnnotations;

            private static ?self \$instance = null;

            /** @var list<PropertyInterface> */
            private array \$declaredProperties;

            /** @var list<NavigationPropertyInterface> */
            private array \$declaredNavigationProperties;

            private bool \$initialized = false;
        {$entityMembers}
            public function __construct()
            {
                \$this->annotations = {$annotations};
        {$props}
                \$this->declaredNavigationProperties = [];
        {$keyInit}
            }

            public static function instance(): self
            {
                return self::\$instance ??= new self();
            }

            /** @internal Called by Edmx after all types are instantiated to break circular refs. */
            public function initNavigationProperties(): void
            {
                if (\$this->initialized) return;
                \$this->initialized = true;
        {$navProps}
            }

            public function getName(): string { return '{$this->e($type->getName())}'; }
            public function getQualifiedName(): string { return '{$this->e($type->getQualifiedName())}'; }
            public function getBaseType(): ?{$interface} { return {$baseType}; }
            public function isAbstract(): bool { return {$this->bool($type->isAbstract())}; }
            public function isOpen(): bool { return {$this->bool($type->isOpen())}; }
            public function getDeclaredProperties(): array { return \$this->declaredProperties; }

            public function getProperty(string \$name): ?PropertyInterface
            {
                foreach (\$this->declaredProperties as \$p) {
                    if (\$p->getName() === \$name) return \$p;
                }
                return \$this->getBaseType()?->getProperty(\$name);
            }

            public function getDeclaredNavigationProperties(): array { return \$this->declaredNavigationProperties; }

            public function getNavigationProperty(string \$name): ?NavigationPropertyInterface
            {
                foreach (\$this->declaredNavigationProperties as \$p) {
                    if (\$p->getName() === \$name) return \$p;
                }
                return \$this->getBaseType()?->getNavigationProperty(\$name);
            }

            public function getAnnotations(): array { return \$this->annotations; }
        }

        PHP;

        $this->writeFile($this->outputDir . '/Types/' . $className . '.php', $this->dedent($code));
        $this->emit("  Types/{$className}.php");
    }

    // ── Entity set generation ───────────────────────────────────────────────

    private function writeEntitySet(EntitySetInterface $set, array $typeMap): void
    {
        $className = $set->getName();
        $ns = $this->namespace . '\\Entities';
        $typeClass = $typeMap[$set->getEntityType()->getQualifiedName()] ?? $set->getEntityType()->getName();
        $typeFqcn = $this->namespace . '\\Types\\' . $typeClass;

        $bindings = $this->generateBindings($set->getNavigationPropertyBindings());
        $annotations = $this->generateAnnotationsCode($set->getAnnotations());

        $code = <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$ns};

        use LaravelUi5\OData\Edm\Container\NavigationPropertyBinding;
        use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
        use LaravelUi5\OData\Edm\Contracts\Container\NavigationPropertyBindingInterface;
        use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
        use LaravelUi5\OData\Edm\HasAnnotations;

        final readonly class {$className} implements EntitySetInterface
        {
            use HasAnnotations;

            private EntityTypeInterface \$entityType;

            /** @var list<NavigationPropertyBindingInterface> */
            private array \$navigationPropertyBindings;

            public function __construct()
            {
                \$this->annotations = {$annotations};
                \$this->entityType = \\{$typeFqcn}::instance();
        {$bindings}
            }

            public function getName(): string { return '{$this->e($set->getName())}'; }
            public function getEntityType(): EntityTypeInterface { return \$this->entityType; }
            public function isIncludedInServiceDocument(): bool { return {$this->bool($set->isIncludedInServiceDocument())}; }
            public function getNavigationPropertyBindings(): array { return \$this->navigationPropertyBindings; }

            public function getNavigationPropertyBinding(string \$path): ?NavigationPropertyBindingInterface
            {
                foreach (\$this->navigationPropertyBindings as \$b) {
                    if (\$b->getPath() === \$path) return \$b;
                }
                return null;
            }

            public function getAnnotations(): array { return \$this->annotations; }
        }

        PHP;

        $this->writeFile($this->outputDir . '/Entities/' . $className . '.php', $this->dedent($code));
        $this->emit("  Entities/{$className}.php");
    }

    // ── Root Edmx generation ────────────────────────────────────────────────

    private function writeEdmx(array $typeMap): void
    {
        $container = $this->edmx->getEntityContainer();
        $schema = array_values($this->edmx->getSchemas())[0] ?? null;
        $schemaNamespace = $schema?->getNamespace() ?? '';
        $schemaAlias = $schema?->getAlias();

        // Build entity set instantiations
        $setInits = [];
        foreach ($container->getEntitySets() as $set) {
            $setClass = $this->namespace . '\\Entities\\' . $set->getName();
            $setInits[] = "            new \\{$setClass}(),";
        }

        // Build singleton instantiations
        $singletonInits = [];
        foreach ($container->getSingletons() as $singleton) {
            $typeClass = $typeMap[$singleton->getEntityType()->getQualifiedName()] ?? $singleton->getEntityType()->getName();
            $typeFqcn = $this->namespace . '\\Types\\' . $typeClass;
            $singletonBindings = implode(', ', array_map(
                fn ($b) => sprintf(
                    'new \\LaravelUi5\\OData\\Edm\\Container\\NavigationPropertyBinding(%s, %s)',
                    $this->literal($b->getPath()),
                    $this->literal($b->getTarget()),
                ),
                $singleton->getNavigationPropertyBindings(),
            ));
            $singletonInits[] = "            new \\LaravelUi5\\OData\\Edm\\Container\\Singleton({$this->literal($singleton->getName())}, \\{$typeFqcn}::instance(), [{$singletonBindings}], {$this->generateAnnotationsCode($singleton->getAnnotations())}),";
        }

        // Build function import instantiations
        $funcImportInits = [];
        foreach ($container->getFunctionImports() as $import) {
            $funcCode = $this->generateFunctionCode($import->getFunction(), $typeMap);
            $funcImportInits[] = sprintf(
                '            new \\LaravelUi5\\OData\\Edm\\Container\\FunctionImport(%s, %s, %s, %s, %s),',
                $this->literal($import->getName()),
                $funcCode,
                $this->literal($import->getEntitySet()),
                $this->bool($import->isIncludedInServiceDocument()),
                $this->generateAnnotationsCode($import->getAnnotations()),
            );
        }

        // Build entity type instantiations for schema
        $typeInits = [];
        foreach ($this->allEntityTypes() as $type) {
            $typeClass = $this->namespace . '\\Types\\' . $type->getName();
            $typeInits[] = "            \\{$typeClass}::instance(),";
        }

        // Build complex type instantiations for schema
        $complexTypeInits = [];
        foreach ($this->allComplexTypes() as $type) {
            $typeClass = $this->namespace . '\\Types\\' . $type->getName();
            $complexTypeInits[] = "            \\{$typeClass}::instance(),";
        }

        // Build enum type instantiations for schema. Without these the CSDL
        // serializer emits no <EnumType> element and a client that resolves the
        // property's type by name finds nothing.
        $enumTypeInits = [];
        foreach ($this->allEnumTypes() as $type) {
            $enumTypeInits[] = '            ' . $this->generateEnumTypeCode($type) . ',';
        }

        // Build type definition instantiations for schema
        $typeDefInits = [];
        foreach ($schema?->getTypeDefinitions() ?? [] as $typeDef) {
            $typeDefInits[] = '            ' . $this->generateTypeDefinitionCode($typeDef) . ',';
        }

        // Build function instantiations for schema
        $funcInits = [];
        if ($schema) {
            foreach ($schema->getFunctions() as $name => $overloads) {
                foreach ($overloads as $func) {
                    $funcInits[] = '            ' . $this->generateFunctionCode($func, $typeMap) . ',';
                }
            }
        }

        // Build initNavigationProperties calls for all entity types
        $navInitCalls = [];
        foreach ([...$this->allEntityTypes(), ...$this->allComplexTypes()] as $type) {
            $typeClass = $this->namespace . '\\Types\\' . $type->getName();
            if ($type->getDeclaredNavigationProperties() !== []) {
                $navInitCalls[] = "            \\{$typeClass}::instance()->initNavigationProperties();";
            }
        }

        $containerAnnotations = $this->generateAnnotationsCode($container->getAnnotations());
        $schemaAnnotations    = $this->generateAnnotationsCode($schema?->getAnnotations() ?? []);
        $references           = $this->generateReferencesCode($this->edmx->getReferences());

        $ns = $this->namespace;
        $version = $this->e($this->edmx->getVersion());
        $containerName = $this->e($container->getName());
        $aliasArg = $schemaAlias !== null ? "'{$this->e($schemaAlias)}'" : 'null';

        $setBlock = implode("\n", $setInits);
        $singletonBlock = implode("\n", $singletonInits);
        $funcImportBlock = implode("\n", $funcImportInits);
        $typeBlock = implode("\n", $typeInits);
        $complexTypeBlock = implode("\n", $complexTypeInits);
        $enumTypeBlock = implode("\n", $enumTypeInits);
        $funcBlock = implode("\n", $funcInits);
        $typeDefBlock = implode("\n", $typeDefInits);
        $navInitBlock = implode("\n", $navInitCalls);

        $code = <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$ns};

        use LaravelUi5\OData\Edm\Container\EntityContainer;
        use LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface;
        use LaravelUi5\OData\Edm\Contracts\EdmxInterface;
        use LaravelUi5\OData\Edm\Contracts\SchemaInterface;
        use LaravelUi5\OData\Edm\Schema;

        /**
         * Generated by EdmxWriter. Do not edit.
         */
        final readonly class Edmx implements EdmxInterface
        {
            private EntityContainerInterface \$container;

            /** @var array<string, SchemaInterface> */
            private array \$schemas;

            /** @var list<\\LaravelUi5\\OData\\Edm\\Contracts\\ReferenceInterface> */
            private array \$references;

            public function __construct()
            {
                \$this->container = new EntityContainer(
                    name: '{$containerName}',
                    entitySets: [
        {$setBlock}
                    ],
                    singletons: [
        {$singletonBlock}
                    ],
                    functionImports: [
        {$funcImportBlock}
                    ],
                    annotations: {$containerAnnotations},
                );

                \$this->schemas = [
                    '{$this->e($schemaNamespace)}' => new Schema(
                        namespace: '{$this->e($schemaNamespace)}',
                        alias: {$aliasArg},
                        entityTypes: [
        {$typeBlock}
                        ],
                        complexTypes: [
        {$complexTypeBlock}
                        ],
                        enumTypes: [
        {$enumTypeBlock}
                        ],
                        typeDefinitions: [
        {$typeDefBlock}
                        ],
                        functions: [
        {$funcBlock}
                        ],
                        annotations: {$schemaAnnotations},
                    ),
                ];

                \$this->references = {$references};

                // Wire navigation properties after all types exist (breaks circular refs).
        {$navInitBlock}
            }

            public function getVersion(): string { return '{$version}'; }
            public function getReferences(): array { return \$this->references; }

            public function getReference(string \$uri): ?\\LaravelUi5\\OData\\Edm\\Contracts\\ReferenceInterface
            {
                foreach (\$this->references as \$reference) {
                    if (\$reference->getUri() === \$uri) return \$reference;
                }
                return null;
            }
            public function getSchemas(): array { return \$this->schemas; }
            public function getSchema(string \$namespace): ?SchemaInterface { return \$this->schemas[\$namespace] ?? null; }
            public function getEntityContainer(): EntityContainerInterface { return \$this->container; }
        }

        PHP;

        $this->writeFile($this->outputDir . '/Edmx.php', $this->dedent($code));
        $this->emit("  Edmx.php");
    }

    // ── Code generation helpers ─────────────────────────────────────────────

    /**
     * Generate property array assignment for declared properties.
     *
     * @param list<PropertyInterface> $properties
     */
    private function generateProperties(array $properties, array $typeMap): string
    {
        $lines = [];
        foreach ($properties as $prop) {
            $args = [
                "'{$this->e($prop->getName())}'",
                $this->generateTypeCode($prop->getType(), $typeMap),
            ];
            if ($prop->isCollection()) {
                $args[] = 'isCollection: true';
            }
            if ($prop->getFacets() !== null) {
                $args[] = 'facets: ' . $this->generateFacetsCode($prop->getFacets());
            }
            if ($prop->getDefaultValue() !== null) {
                $args[] = "defaultValue: '{$this->e($prop->getDefaultValue())}'";
            }
            if ($prop->getAnnotations() !== []) {
                $args[] = 'annotations: ' . $this->generateAnnotationsCode($prop->getAnnotations());
            }
            $lines[] = '            new Property(' . implode(', ', $args) . '),';
        }

        $block = implode("\n", $lines);
        return "        \$this->declaredProperties = [\n{$block}\n        ];";
    }

    /**
     * Generate PHP code for a property's type facets.
     *
     * Every facet is written, defaults included, so the cached property
     * answers each facet getter exactly as the one discovery built.
     */
    private function generateFacetsCode(TypeFacetsInterface $facets): string
    {
        $int = static fn (?int $v): string => $v === null ? 'null' : (string) $v;
        $unicode = $facets->isUnicode() === null ? 'null' : $this->bool($facets->isUnicode());

        return 'new \\LaravelUi5\\OData\\Edm\\Type\\TypeFacets('
            . "nullable: {$this->bool($facets->isNullable())}, "
            . "maxLength: {$int($facets->getMaxLength())}, "
            . "precision: {$int($facets->getPrecision())}, "
            . "scale: {$int($facets->getScale())}, "
            . "unicode: {$unicode}, "
            . "srid: {$int($facets->getSrid())})";
    }

    /**
     * Generate navigation property array assignment.
     *
     * @param list<NavigationPropertyInterface> $navProps
     */
    private function generateNavigationProperties(array $navProps, array $typeMap): string
    {
        if ($navProps === []) {
            return "        \$this->declaredNavigationProperties = [];";
        }

        $lines = [];
        foreach ($navProps as $nav) {
            $targetName = $nav->getTargetType()->getName();
            $targetClass = $typeMap[$nav->getTargetType()->getQualifiedName()] ?? $targetName;
            $targetFqcn = $this->namespace . '\\Types\\' . $targetClass;

            $args = ["name: '{$this->e($nav->getName())}'"];
            $args[] = "targetType: \\{$targetFqcn}::instance()";
            $args[] = "isCollection: {$this->bool($nav->isCollection())}";

            if (!$nav->isNullable()) {
                $args[] = "isNullable: false";
            }
            if ($nav->getPartnerName() !== null) {
                $args[] = "partnerName: '{$this->e($nav->getPartnerName())}'";
            }
            if ($nav->getReferentialConstraints() !== []) {
                $constraints = $this->generateArrayLiteral($nav->getReferentialConstraints());
                $args[] = "referentialConstraints: {$constraints}";
            }
            if ($nav->isContainmentTarget()) {
                $args[] = 'isContainmentTarget: true';
            }
            if ($nav->getOnDeleteAction() !== null) {
                $args[] = "onDeleteAction: {$this->literal($nav->getOnDeleteAction())}";
            }
            if ($nav->getAnnotations() !== []) {
                $args[] = 'annotations: ' . $this->generateAnnotationsCode($nav->getAnnotations());
            }

            $argStr = implode(', ', $args);
            $lines[] = "            new NavigationProperty({$argStr}),";
        }

        $block = implode("\n", $lines);
        return "        \$this->declaredNavigationProperties = [\n{$block}\n        ];";
    }

    /**
     * Key property references by name — indexes into declaredProperties, wherever
     * the key columns sit among them.
     */
    private function generateKeyReferences(EntityTypeInterface $type): string
    {
        // Only the type's own key; a derived type with none falls back to its base at runtime.
        $declared = $type->getDeclaredProperties();
        $refs     = [];
        foreach ($type->getKey() as $keyProp) {
            foreach ($declared as $i => $prop) {
                if ($prop->getName() === $keyProp->getName()) {
                    $refs[] = "\$this->declaredProperties[{$i}]";
                    continue 2;
                }
            }
        }

        return implode(', ', $refs);
    }

    /**
     * Generate navigation property binding array assignment.
     *
     * @param list<NavigationPropertyBindingInterface> $bindings
     */
    private function generateBindings(array $bindings): string
    {
        if ($bindings === []) {
            return "        \$this->navigationPropertyBindings = [];";
        }

        $lines = [];
        foreach ($bindings as $b) {
            $lines[] = "            new NavigationPropertyBinding('{$this->e($b->getPath())}', '{$this->e($b->getTarget())}'),";
        }

        $block = implode("\n", $lines);
        return "        \$this->navigationPropertyBindings = [\n{$block}\n        ];";
    }

    /**
     * Generate PHP code for a TypeInterface value.
     */
    private function generateTypeCode(TypeInterface $type, array $typeMap): string
    {
        if ($type instanceof \LaravelUi5\OData\Edm\Contracts\Type\PrimitiveTypeInterface) {
            $enumCase = $type->getPrimitiveType()->name;
            return "new \\LaravelUi5\\OData\\Edm\\Type\\PrimitiveType(\\LaravelUi5\\OData\\Edm\\EdmPrimitiveType::{$enumCase})";
        }

        if ($type instanceof EnumTypeInterface) {
            return $this->generateEnumTypeCode($type);
        }

        if ($type instanceof TypeDefinitionInterface) {
            return $this->generateTypeDefinitionCode($type);
        }

        if ($type instanceof EntityTypeInterface || $type instanceof ComplexTypeInterface) {
            $className = $typeMap[$type->getQualifiedName()] ?? $type->getName();
            $fqcn = $this->namespace . '\\Types\\' . $className;
            return "\\{$fqcn}::instance()";
        }

        throw new \LogicException(sprintf(
            'odata:cache cannot write a type of class %s (%s).',
            get_debug_type($type),
            $type->getQualifiedName(),
        ));
    }

    /**
     * A TypeDefinition inline, like an enum type: a value object without circular
     * references. Property sites and the schema hold equal, distinct instances.
     */
    private function generateTypeDefinitionCode(TypeDefinitionInterface $type): string
    {
        $namespace = substr($type->getQualifiedName(), 0, -(strlen($type->getName()) + 1));

        return sprintf(
            'new \\LaravelUi5\\OData\\Edm\\Type\\TypeDefinition(%s, %s, \\LaravelUi5\\OData\\Edm\\EdmPrimitiveType::%s, %s, %s)',
            $this->literal($namespace),
            $this->literal($type->getName()),
            $type->getUnderlyingType()->name,
            $type->getFacets() !== null ? $this->generateFacetsCode($type->getFacets()) : 'null',
            $this->generateAnnotationsCode($type->getAnnotations()),
        );
    }

    /**
     * Generate PHP code for an EnumTypeInterface.
     *
     * Emitted inline rather than as a generated class in `Types/`: an
     * `EnumType` is a final readonly value object with a plain constructor and
     * no circular references, so there is nothing for an `instance()` seam to
     * solve. Property sites and the schema's `enumTypes` therefore hold equal
     * but distinct instances — which is what the cold path produces too, since
     * `entityType()` calls `EnumType::fromBackedEnum()` per column.
     */
    private function generateEnumTypeCode(EnumTypeInterface $type): string
    {
        $members = [];
        foreach ($type->getMembers() as $member) {
            $members[] = sprintf(
                "new \\LaravelUi5\\OData\\Edm\\Container\\EnumMember('%s', %d%s)",
                $this->e($member->getName()),
                $member->getValue(),
                $this->optionalAnnotationsArg($member->getAnnotations()),
            );
        }

        // EnumTypeInterface exposes no getNamespace(); the qualified name is
        // "{namespace}.{name}", so the prefix is what remains once the name goes.
        $namespace = substr($type->getQualifiedName(), 0, -(strlen($type->getName()) + 1));

        return sprintf(
            // Fully qualified: the same literal is emitted into Types/*.php,
            // which imports EdmPrimitiveType, and into Edmx.php, which does not.
            "new \\LaravelUi5\\OData\\Edm\\Container\\EnumType('%s', '%s', \\LaravelUi5\\OData\\Edm\\EdmPrimitiveType::%s, %s, [%s]%s)",
            $this->e($namespace),
            $this->e($type->getName()),
            $type->getUnderlyingType()->name,
            $this->bool($type->isFlags()),
            implode(', ', $members),
            $this->optionalAnnotationsArg($type->getAnnotations()),
        );
    }

    /**
     * Every enum type the schemas declare, deduplicated by qualified name.
     *
     * @return list<EnumTypeInterface>
     */
    private function allEnumTypes(): array
    {
        $types = [];
        foreach ($this->edmx->getSchemas() as $schema) {
            foreach ($schema->getEnumTypes() as $type) {
                $types[$type->getQualifiedName()] = $type;
            }
        }
        return array_values($types);
    }

    /**
     * Generate PHP code for a FunctionInterface.
     */
    private function generateFunctionCode(FunctionInterface $func, array $typeMap = []): string
    {
        $params = [];
        foreach ($func->getParameters() as $param) {
            $args = [
                $this->literal($param->getName()),
                $this->generateTypeCode($param->getType(), $typeMap),
            ];
            if ($param->isCollection()) {
                $args[] = 'isCollection: true';
            }
            if (!$param->isNullable()) {
                $args[] = 'isNullable: false';
            }
            if ($param->getFacets() !== null) {
                $args[] = 'facets: ' . $this->generateFacetsCode($param->getFacets());
            }
            if ($param->getAnnotations() !== []) {
                $args[] = 'annotations: ' . $this->generateAnnotationsCode($param->getAnnotations());
            }
            $params[] = 'new \\LaravelUi5\\OData\\Edm\\FunctionParameter(' . implode(', ', $args) . ')';
        }

        $args = ["name: {$this->literal($func->getName())}"];

        if ($func->isBound()) {
            $args[] = 'isBound: true';
        }
        if ($func->isComposable()) {
            $args[] = 'isComposable: true';
        }
        if ($func->getReturnType() !== null) {
            $args[] = "returnType: {$this->generateTypeCode($func->getReturnType(), $typeMap)}";
        }
        if ($func->returnsCollection()) {
            $args[] = 'returnsCollection: true';
        }
        if (!$func->isReturnTypeNullable()) {
            $args[] = 'isReturnTypeNullable: false';
        }
        if ($params !== []) {
            $args[] = 'parameters: [' . implode(', ', $params) . ']';
        }
        if ($func->getEntitySetPath() !== null) {
            $args[] = sprintf(
                'entitySetPath: new \\LaravelUi5\\OData\\Edm\\EntitySetPath(%s, %s)',
                $this->literal($func->getEntitySetPath()->getBindingParameterName()),
                $this->literal($func->getEntitySetPath()->getNavigationPropertyName()),
            );
        }
        if ($func->getAnnotations() !== []) {
            $args[] = 'annotations: ' . $this->generateAnnotationsCode($func->getAnnotations());
        }

        return 'new \\LaravelUi5\\OData\\Edm\\EdmFunction(' . implode(', ', $args) . ')';
    }

    // ── Annotation generation ───────────────────────────────────────────────

    /**
     * Generate a PHP array literal of annotations, fully qualified so the same
     * code works in Types/, Entities/ and Edmx.php alike.
     *
     * Typed vocabulary annotations (`#[Label]`, `#[LineItem]`, …) are already
     * generic `Annotation`s by the time they reach the Edm (AttributeReader calls
     * `toAnnotation()`), so the generic shape is all the cache has to carry.
     *
     * @param list<AnnotationInterface> $annotations
     */
    private function generateAnnotationsCode(array $annotations): string
    {
        if ($annotations === []) {
            return '[]';
        }

        return '[' . implode(', ', array_map($this->generateAnnotationCode(...), $annotations)) . ']';
    }

    /**
     * `, <annotations>` as a trailing positional argument, or nothing — keeps the
     * generated code unchanged for the common case of no annotations.
     *
     * @param list<AnnotationInterface> $annotations
     */
    private function optionalAnnotationsArg(array $annotations): string
    {
        return $annotations === [] ? '' : ', ' . $this->generateAnnotationsCode($annotations);
    }

    private function generateAnnotationCode(AnnotationInterface $annotation): string
    {
        return sprintf(
            'new \\LaravelUi5\\OData\\Edm\\Annotation\\Annotation(%s, %s, %s)',
            $this->literal($annotation->getTerm()),
            $this->literal($annotation->getQualifier()),
            $annotation->getValue() === null ? 'null' : $this->generateAnnotationValueCode($annotation->getValue()),
        );
    }

    private function generateAnnotationValueCode(AnnotationValueInterface $value): string
    {
        if ($value instanceof ConstantAnnotationValueInterface) {
            return sprintf(
                'new \\LaravelUi5\\OData\\Edm\\Annotation\\ConstantAnnotationValue(%s, %s)',
                $this->literal($value->getKind()),
                $this->literal($value->getValue()),
            );
        }

        if ($value instanceof RecordAnnotationValueInterface) {
            $args = [$this->literal($value->getType())];
            foreach ($value->getPropertyValues() as $pv) {
                $args[] = sprintf(
                    'new \\LaravelUi5\\OData\\Edm\\Annotation\\PropertyValue(%s, %s)',
                    $this->literal($pv->getProperty()),
                    $this->generateAnnotationValueCode($pv->getValue()),
                );
            }
            return 'new \\LaravelUi5\\OData\\Edm\\Annotation\\RecordAnnotationValue(' . implode(', ', $args) . ')';
        }

        if ($value instanceof CollectionAnnotationValueInterface) {
            $items = array_map($this->generateAnnotationValueCode(...), $value->getItems());
            return 'new \\LaravelUi5\\OData\\Edm\\Annotation\\CollectionAnnotationValue(' . implode(', ', $items) . ')';
        }

        throw new \LogicException(sprintf(
            'odata:cache cannot write an annotation value of type %s; the serializer would not write it either.',
            get_debug_type($value),
        ));
    }

    /**
     * @param list<\LaravelUi5\OData\Edm\Contracts\ReferenceInterface> $references
     */
    private function generateReferencesCode(array $references): string
    {
        if ($references === []) {
            return '[]';
        }

        $items = [];
        foreach ($references as $reference) {
            $includes = array_map(
                fn ($include) => sprintf(
                    'new \\LaravelUi5\\OData\\Edm\\IncludedSchema(%s, %s)',
                    $this->literal($include->getNamespace()),
                    $this->literal($include->getAlias()),
                ),
                $reference->getIncludes(),
            );
            $items[] = sprintf(
                'new \\LaravelUi5\\OData\\Edm\\Reference(%s, [%s], %s)',
                $this->literal($reference->getUri()),
                implode(', ', $includes),
                $this->generateAnnotationsCode($reference->getAnnotations()),
            );
        }

        return '[' . implode(', ', $items) . ']';
    }

    /**
     * A PHP literal for a string or null. `var_export` quotes exactly what a
     * single-quoted literal needs; `e()` (addslashes) would also escape `"`,
     * which a single-quoted literal keeps as `\"` — wrong for annotation text.
     */
    private function literal(?string $value): string
    {
        return var_export($value, true) === 'NULL' ? 'null' : var_export($value, true);
    }

    // ── Utility ─────────────────────────────────────────────────────────────

    /**
     * Collect all unique entity types from all schemas.
     *
     * @return list<EntityTypeInterface>
     */
    private function allEntityTypes(): array
    {
        $types = [];
        foreach ($this->edmx->getSchemas() as $schema) {
            foreach ($schema->getEntityTypes() as $type) {
                $types[$type->getQualifiedName()] = $type;
            }
        }
        return array_values($types);
    }

    /**
     * @return list<ComplexTypeInterface>
     */
    private function allComplexTypes(): array
    {
        $types = [];
        foreach ($this->edmx->getSchemas() as $schema) {
            foreach ($schema->getComplexTypes() as $type) {
                $types[$type->getQualifiedName()] = $type;
            }
        }
        return array_values($types);
    }

    private function generateArrayLiteral(array $map): string
    {
        $items = [];
        foreach ($map as $k => $v) {
            $items[] = "'{$this->e($k)}' => '{$this->e($v)}'";
        }
        return '[' . implode(', ', $items) . ']';
    }

    private function bool(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    private function e(string $value): string
    {
        return addslashes($value);
    }

    private function dedent(string $code): string
    {
        // Remove the 8-space indent from heredoc
        return preg_replace('/^        /m', '', $code);
    }

    private function writeFile(string $path, string $content): void
    {
        file_put_contents($path, $content);
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    private function emit(string $line): void
    {
        if ($this->output !== null) {
            ($this->output)($line);
        }
    }
}
