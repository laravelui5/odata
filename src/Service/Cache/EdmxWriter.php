<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Cache;

use Closure;
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
        $className = $type->getName();
        $ns = $this->namespace . '\\Types';

        $props = $this->generateProperties($type->getDeclaredProperties(), $typeMap);
        $navProps = $this->generateNavigationProperties($type->getDeclaredNavigationProperties(), $typeMap);
        $key = $this->generateKeyReferences($type->getKey());

        $code = <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$ns};

        use LaravelUi5\OData\Edm\EdmPrimitiveType;
        use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
        use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
        use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
        use LaravelUi5\OData\Edm\HasAnnotations;
        use LaravelUi5\OData\Edm\Property\NavigationProperty;
        use LaravelUi5\OData\Edm\Property\Property;
        use LaravelUi5\OData\Edm\Type\PrimitiveType;

        final class {$className} implements EntityTypeInterface
        {
            use HasAnnotations;

            private static ?self \$instance = null;

            /** @var list<PropertyInterface> */
            private array \$key;

            /** @var list<PropertyInterface> */
            private array \$declaredProperties;

            /** @var list<NavigationPropertyInterface> */
            private array \$declaredNavigationProperties;

            private bool \$initialized = false;

            public function __construct()
            {
                \$this->annotations = [];
        {$props}
                \$this->declaredNavigationProperties = [];
                \$this->key = [{$key}];
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
            public function getBaseType(): ?EntityTypeInterface { return null; }
            public function isAbstract(): bool { return false; }
            public function isOpen(): bool { return false; }
            public function hasStream(): bool { return {$this->bool($type->hasStream())}; }
            public function getKey(): array { return \$this->key; }
            public function getDeclaredProperties(): array { return \$this->declaredProperties; }

            public function getProperty(string \$name): ?PropertyInterface
            {
                foreach (\$this->declaredProperties as \$p) {
                    if (\$p->getName() === \$name) return \$p;
                }
                return null;
            }

            public function getDeclaredNavigationProperties(): array { return \$this->declaredNavigationProperties; }

            public function getNavigationProperty(string \$name): ?NavigationPropertyInterface
            {
                foreach (\$this->declaredNavigationProperties as \$p) {
                    if (\$p->getName() === \$name) return \$p;
                }
                return null;
            }

            public function getAnnotations(): array { return \$this->annotations; }
            public function getAnnotation(string \$term, ?string \$qualifier = null): ?\\LaravelUi5\\OData\\Edm\\Contracts\\Annotation\\AnnotationInterface { return null; }
        }

        PHP;

        $this->writeFile($this->outputDir . '/Types/' . $className . '.php', $this->dedent($code));
        $this->emit("  Types/{$className}.php");
    }

    // ── Complex type generation ─────────────────────────────────────────────

    private function writeComplexType(ComplexTypeInterface $type, array $typeMap): void
    {
        $className = $type->getName();
        $ns = $this->namespace . '\\Types';

        $props = $this->generateProperties($type->getDeclaredProperties(), $typeMap);

        $code = <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$ns};

        use LaravelUi5\OData\Edm\EdmPrimitiveType;
        use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
        use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
        use LaravelUi5\OData\Edm\Contracts\Type\ComplexTypeInterface;
        use LaravelUi5\OData\Edm\HasAnnotations;
        use LaravelUi5\OData\Edm\Property\Property;
        use LaravelUi5\OData\Edm\Type\PrimitiveType;

        final readonly class {$className} implements ComplexTypeInterface
        {
            use HasAnnotations;

            /** @var list<PropertyInterface> */
            private array \$declaredProperties;

            public function __construct()
            {
                \$this->annotations = [];
        {$props}
            }

            public function getName(): string { return '{$this->e($type->getName())}'; }
            public function getQualifiedName(): string { return '{$this->e($type->getQualifiedName())}'; }
            public function getBaseType(): ?ComplexTypeInterface { return null; }
            public function isAbstract(): bool { return false; }
            public function isOpen(): bool { return false; }
            public function getDeclaredProperties(): array { return \$this->declaredProperties; }

            public function getProperty(string \$name): ?PropertyInterface
            {
                foreach (\$this->declaredProperties as \$p) {
                    if (\$p->getName() === \$name) return \$p;
                }
                return null;
            }

            public function getDeclaredNavigationProperties(): array { return []; }
            public function getNavigationProperty(string \$name): ?NavigationPropertyInterface { return null; }
            public function getAnnotations(): array { return \$this->annotations; }
            public function getAnnotation(string \$term, ?string \$qualifier = null): ?\\LaravelUi5\\OData\\Edm\\Contracts\\Annotation\\AnnotationInterface { return null; }
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

        $code = <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$ns};

        use LaravelUi5\OData\Edm\Container\NavigationPropertyBinding;
        use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
        use LaravelUi5\OData\Edm\Contracts\Container\NavigationPropertyBindingInterface;
        use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
        use LaravelUi5\OData\Edm\HasAnnotations;
        use {$typeFqcn};

        final readonly class {$className} implements EntitySetInterface
        {
            use HasAnnotations;

            private EntityTypeInterface \$entityType;

            /** @var list<NavigationPropertyBindingInterface> */
            private array \$navigationPropertyBindings;

            public function __construct()
            {
                \$this->annotations = [];
                \$this->entityType = {$typeClass}::instance();
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
            public function getAnnotation(string \$term, ?string \$qualifier = null): ?\\LaravelUi5\\OData\\Edm\\Contracts\\Annotation\\AnnotationInterface { return null; }
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
            $singletonInits[] = "            new \\LaravelUi5\\OData\\Edm\\Container\\Singleton('{$this->e($singleton->getName())}', \\{$typeFqcn}::instance()),";
        }

        // Build function import instantiations
        $funcImportInits = [];
        foreach ($container->getFunctionImports() as $import) {
            $funcCode = $this->generateFunctionCode($import->getFunction());
            $funcImportInits[] = "            new \\LaravelUi5\\OData\\Edm\\Container\\FunctionImport('{$this->e($import->getName())}', {$funcCode}),";
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

        // Build function instantiations for schema
        $funcInits = [];
        if ($schema) {
            foreach ($schema->getFunctions() as $name => $overloads) {
                foreach ($overloads as $func) {
                    $funcInits[] = '            ' . $this->generateFunctionCode($func) . ',';
                }
            }
        }

        // Build initNavigationProperties calls for all entity types
        $navInitCalls = [];
        foreach ($this->allEntityTypes() as $type) {
            $typeClass = $this->namespace . '\\Types\\' . $type->getName();
            if ($type->getDeclaredNavigationProperties() !== []) {
                $navInitCalls[] = "            \\{$typeClass}::instance()->initNavigationProperties();";
            }
        }

        $ns = $this->namespace;
        $version = $this->e($this->edmx->getVersion());
        $containerName = $this->e($container->getName());
        $aliasArg = $schemaAlias !== null ? "'{$this->e($schemaAlias)}'" : 'null';

        $setBlock = implode("\n", $setInits);
        $singletonBlock = implode("\n", $singletonInits);
        $funcImportBlock = implode("\n", $funcImportInits);
        $typeBlock = implode("\n", $typeInits);
        $complexTypeBlock = implode("\n", $complexTypeInits);
        $funcBlock = implode("\n", $funcInits);
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
                        functions: [
        {$funcBlock}
                        ],
                    ),
                ];

                // Wire navigation properties after all types exist (breaks circular refs).
        {$navInitBlock}
            }

            public function getVersion(): string { return '{$version}'; }
            public function getReferences(): array { return []; }
            public function getReference(string \$uri): ?\\LaravelUi5\\OData\\Edm\\Contracts\\ReferenceInterface { return null; }
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
            $typeCode = $this->generateTypeCode($prop->getType(), $typeMap);
            $lines[] = "            new Property('{$this->e($prop->getName())}', {$typeCode}),";
        }

        $block = implode("\n", $lines);
        return "        \$this->declaredProperties = [\n{$block}\n        ];";
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

            $argStr = implode(', ', $args);
            $lines[] = "            new NavigationProperty({$argStr}),";
        }

        $block = implode("\n", $lines);
        return "        \$this->declaredNavigationProperties = [\n{$block}\n        ];";
    }

    /**
     * Generate key property references (indexes into declaredProperties).
     *
     * @param list<PropertyInterface> $keyProps
     */
    private function generateKeyReferences(array $keyProps): string
    {
        if ($keyProps === []) {
            return '';
        }

        $refs = [];
        foreach ($keyProps as $i => $kp) {
            $refs[] = "\$this->declaredProperties[{$i}]";
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
            return "new PrimitiveType(EdmPrimitiveType::{$enumCase})";
        }

        if ($type instanceof EntityTypeInterface || $type instanceof ComplexTypeInterface) {
            $className = $typeMap[$type->getQualifiedName()] ?? $type->getName();
            $fqcn = $this->namespace . '\\Types\\' . $className;
            return "new \\{$fqcn}()";
        }

        // Fallback for unknown types
        return "new PrimitiveType(EdmPrimitiveType::String)";
    }

    /**
     * Generate PHP code for a FunctionInterface.
     */
    private function generateFunctionCode(FunctionInterface $func): string
    {
        $params = [];
        foreach ($func->getParameters() as $param) {
            $typeCode = $this->generateParamTypeCode($param->getType());
            $params[] = "new \\LaravelUi5\\OData\\Edm\\FunctionParameter('{$this->e($param->getName())}', {$typeCode})";
        }

        $args = ["name: '{$this->e($func->getName())}'"];

        if ($func->getReturnType() !== null) {
            $args[] = "returnType: {$this->generateParamTypeCode($func->getReturnType())}";
        }

        if ($params !== []) {
            $paramStr = implode(', ', $params);
            $args[] = "parameters: [{$paramStr}]";
        }

        return 'new \\LaravelUi5\\OData\\Edm\\EdmFunction(' . implode(', ', $args) . ')';
    }

    /**
     * Generate type code for function parameters (always uses FQCN).
     */
    private function generateParamTypeCode(TypeInterface $type): string
    {
        if ($type instanceof \LaravelUi5\OData\Edm\Contracts\Type\PrimitiveTypeInterface) {
            $enumCase = $type->getPrimitiveType()->name;
            return "new \\LaravelUi5\\OData\\Edm\\Type\\PrimitiveType(\\LaravelUi5\\OData\\Edm\\EdmPrimitiveType::{$enumCase})";
        }

        return "new \\LaravelUi5\\OData\\Edm\\Type\\PrimitiveType(\\LaravelUi5\\OData\\Edm\\EdmPrimitiveType::String)";
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
