<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EnumTypeInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionInterface;
use LaravelUi5\OData\Edm\Contracts\SchemaInterface;
use LaravelUi5\OData\Edm\Contracts\Type\ComplexTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeDefinitionInterface;

final readonly class Schema implements SchemaInterface
{
    use HasAnnotations;

    /**
     * @param list<EntityTypeInterface>    $entityTypes
     * @param list<ComplexTypeInterface>   $complexTypes
     * @param list<EnumTypeInterface>      $enumTypes
     * @param list<TypeDefinitionInterface> $typeDefinitions
     * @param list<FunctionInterface>      $functions        all overloads flat; getFunctions() groups by name
     * @param list<AnnotationInterface> $annotations
     */
    public function __construct(
        private string  $namespace,
        private ?string $alias           = null,
        private array   $entityTypes     = [],
        private array   $complexTypes    = [],
        private array   $enumTypes       = [],
        private array   $typeDefinitions = [],
        private array   $functions       = [],
        array           $annotations     = [],
    ) {
        $this->annotations = $annotations;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    // ── Entity types ─────────────────────────────────────────────────────────

    public function getEntityTypes(): array
    {
        return $this->entityTypes;
    }

    public function getEntityType(string $name): ?EntityTypeInterface
    {
        foreach ($this->entityTypes as $type) {
            if ($type->getName() === $name) {
                return $type;
            }
        }
        return null;
    }

    // ── Complex types ─────────────────────────────────────────────────────────

    public function getComplexTypes(): array
    {
        return $this->complexTypes;
    }

    public function getComplexType(string $name): ?ComplexTypeInterface
    {
        foreach ($this->complexTypes as $type) {
            if ($type->getName() === $name) {
                return $type;
            }
        }
        return null;
    }

    // ── Enum types ────────────────────────────────────────────────────────────

    public function getEnumTypes(): array
    {
        return $this->enumTypes;
    }

    public function getEnumType(string $name): ?EnumTypeInterface
    {
        foreach ($this->enumTypes as $type) {
            if ($type->getName() === $name) {
                return $type;
            }
        }
        return null;
    }

    // ── Type definitions ──────────────────────────────────────────────────────

    public function getTypeDefinitions(): array
    {
        return $this->typeDefinitions;
    }

    public function getTypeDefinition(string $name): ?TypeDefinitionInterface
    {
        foreach ($this->typeDefinitions as $def) {
            if ($def->getName() === $name) {
                return $def;
            }
        }
        return null;
    }

    // ── Functions ─────────────────────────────────────────────────────────────

    public function getFunctions(): array
    {
        $grouped = [];
        foreach ($this->functions as $function) {
            $grouped[$function->getName()][] = $function;
        }
        return $grouped;
    }

    public function getFunction(string $name): array
    {
        $result = [];
        foreach ($this->functions as $function) {
            if ($function->getName() === $name) {
                $result[] = $function;
            }
        }
        return $result;
    }
}
