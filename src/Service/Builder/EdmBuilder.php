<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Builder;

use LaravelUi5\OData\Edm\Container\EntityContainer;
use LaravelUi5\OData\Edm\Container\EntitySet;
use LaravelUi5\OData\Edm\Container\NavigationPropertyBinding;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Container\FunctionImportInterface;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EnumTypeInterface;
use LaravelUi5\OData\Edm\Contracts\EdmxInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionInterface;
use LaravelUi5\OData\Edm\Contracts\ReferenceInterface;
use LaravelUi5\OData\Edm\Contracts\Type\ComplexTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeDefinitionInterface;
use LaravelUi5\OData\Edm\Edmx;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Schema;
use LaravelUi5\OData\Edm\Vocabularies\Vocabulary;
use LaravelUi5\OData\Edm\Vocabularies\VocabularyCatalog;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;

/**
 * Mutable accumulator that produces a frozen EdmxInterface (Stage 1).
 *
 * Collect schema elements via the fluent API then call build() once.
 * After build() the builder must not be mutated further.
 */
final class EdmBuilder implements EdmBuilderInterface
{
    private string  $namespace     = '';
    private ?string $alias         = null;
    private string  $containerName = 'DefaultContainer';
    private string  $version       = '4.0';
    private bool    $built         = false;

    /** @var list<ReferenceInterface> */
    private array $references = [];

    /** @var list<EntityTypeInterface> */
    private array $entityTypes = [];

    /** @var list<ComplexTypeInterface> */
    private array $complexTypes = [];

    /** @var array<string, EnumTypeInterface> indexed by qualified name */
    private array $enumTypes = [];

    /** @var list<TypeDefinitionInterface> */
    private array $typeDefinitions = [];

    /** @var list<FunctionInterface> */
    private array $functions = [];

    /** @var list<EntitySetInterface> */
    private array $entitySets = [];

    /** @var list<SingletonInterface> */
    private array $singletons = [];

    /** @var list<FunctionImportInterface> */
    private array $functionImports = [];

    // ── Schema identity ────────────────────────────────────────────────────────

    public function version(string $version): static
    {
        $this->assertNotBuilt();
        $this->version = $version;
        return $this;
    }

    public function namespace(string $namespace): static
    {
        $this->assertNotBuilt();
        $this->namespace = $namespace;
        return $this;
    }

    public function alias(string $alias): static
    {
        $this->assertNotBuilt();
        $this->alias = $alias;
        return $this;
    }

    public function containerName(string $name): static
    {
        $this->assertNotBuilt();
        $this->containerName = $name;
        return $this;
    }

    // ── References ─────────────────────────────────────────────────────────────

    public function addReference(ReferenceInterface $reference): static
    {
        $this->assertNotBuilt();
        $this->references[] = $reference;
        return $this;
    }

    public function useVocabulary(Vocabulary $vocabulary): static
    {
        $entry = VocabularyCatalog::default()->getEntry($vocabulary->value);

        if ($entry === null) {
            throw new \LogicException("Unknown vocabulary: {$vocabulary->value}");
        }

        return $this->addReference($entry->toReference());
    }

    // ── Types ──────────────────────────────────────────────────────────────────

    public function addEntityType(EntityTypeInterface $type): static
    {
        $this->assertNotBuilt();
        $this->entityTypes[] = $type;

        foreach ($type->getDeclaredProperties() as $property) {
            $propertyType = $property->getType();
            if ($propertyType instanceof EnumTypeInterface) {
                $this->addEnumType($propertyType);
            }
        }

        return $this;
    }

    public function addComplexType(ComplexTypeInterface $type): static
    {
        $this->assertNotBuilt();
        $this->complexTypes[] = $type;
        return $this;
    }

    public function addEnumType(EnumTypeInterface $type): static
    {
        $this->assertNotBuilt();

        $qualifiedName = $type->getQualifiedName();
        $existing      = $this->enumTypes[$qualifiedName] ?? null;

        if ($existing === null) {
            $this->enumTypes[$qualifiedName] = $type;
            return $this;
        }

        if (!self::enumTypesEqual($existing, $type)) {
            throw new \LogicException(sprintf(
                'EnumType "%s" already registered with a different definition. '
                . 'Two PHP backed enums collide on the EDM short name within this service; '
                . 'rename one of the source enums or place them in different OData services.',
                $qualifiedName,
            ));
        }

        return $this;
    }

    private static function enumTypesEqual(EnumTypeInterface $a, EnumTypeInterface $b): bool
    {
        if ($a->getUnderlyingType() !== $b->getUnderlyingType()) {
            return false;
        }
        if ($a->isFlags() !== $b->isFlags()) {
            return false;
        }

        $aMembers = $a->getMembers();
        $bMembers = $b->getMembers();

        if (count($aMembers) !== count($bMembers)) {
            return false;
        }

        $bByName = [];
        foreach ($bMembers as $member) {
            $bByName[$member->getName()] = $member->getValue();
        }

        foreach ($aMembers as $member) {
            $name = $member->getName();
            if (!array_key_exists($name, $bByName) || $bByName[$name] !== $member->getValue()) {
                return false;
            }
        }

        return true;
    }

    public function addTypeDefinition(TypeDefinitionInterface $type): static
    {
        $this->assertNotBuilt();
        $this->typeDefinitions[] = $type;
        return $this;
    }

    public function addFunction(FunctionInterface $function): static
    {
        $this->assertNotBuilt();
        $this->functions[] = $function;
        return $this;
    }

    // ── Container members (no resolvers at this stage) ─────────────────────────

    public function addEntitySet(EntitySetInterface $set): static
    {
        $this->assertNotBuilt();
        $this->entitySets[] = $set;
        return $this;
    }

    /**
     * Inject a navigation property into an existing entity type and its entity set.
     *
     * Since entity types and sets are immutable, this replaces them with new
     * instances that include the additional navigation property and binding.
     * Must be called before build().
     */
    public function injectNavigationProperty(
        string                      $entityTypeName,
        NavigationPropertyInterface $navProperty,
        string                      $targetEntitySetName,
    ): static {
        $this->assertNotBuilt();

        // Replace the entity type with one that includes the new nav property
        foreach ($this->entityTypes as $i => $type) {
            if ($type->getName() === $entityTypeName) {
                $existingNavProps = $type->getDeclaredNavigationProperties();
                $existingNavProps[] = $navProperty;

                $this->entityTypes[$i] = new EntityType(
                    namespace:                    $type->getQualifiedName() !== $type->getName()
                        ? substr($type->getQualifiedName(), 0, -strlen($type->getName()) - 1)
                        : '',
                    name:                         $type->getName(),
                    baseType:                     $type->getBaseType(),
                    isAbstract:                   $type->isAbstract(),
                    isOpen:                       $type->isOpen(),
                    hasStream:                    $type->hasStream(),
                    key:                          $type->getKey(),
                    declaredProperties:           $type->getDeclaredProperties(),
                    declaredNavigationProperties: $existingNavProps,
                    annotations:                  $type->getAnnotations(),
                );
                break;
            }
        }

        // Replace the entity set with one that includes the new nav binding
        foreach ($this->entitySets as $j => $set) {
            if ($set->getEntityType()->getName() === $entityTypeName) {
                $existingBindings = $set->getNavigationPropertyBindings();
                $existingBindings[] = new NavigationPropertyBinding(
                    $navProperty->getName(),
                    $targetEntitySetName,
                );

                $this->entitySets[$j] = new EntitySet(
                    name:                       $set->getName(),
                    entityType:                 $this->entityTypes[array_search($entityTypeName, array_map(fn($t) => $t->getName(), $this->entityTypes))],
                    includedInServiceDocument:  $set->isIncludedInServiceDocument(),
                    navigationPropertyBindings: $existingBindings,
                    annotations:                $set->getAnnotations(),
                );
                break;
            }
        }

        return $this;
    }

    public function addSingleton(SingletonInterface $singleton): static
    {
        $this->assertNotBuilt();
        $this->singletons[] = $singleton;
        return $this;
    }

    public function addFunctionImport(FunctionImportInterface $import): static
    {
        $this->assertNotBuilt();
        $this->functionImports[] = $import;
        return $this;
    }

    // ── Produce the frozen model ───────────────────────────────────────────────

    public function build(): EdmxInterface
    {
        $this->assertNotBuilt();
        $this->built = true;

        $schema = new Schema(
            namespace:       $this->namespace,
            alias:           $this->alias,
            entityTypes:     $this->entityTypes,
            complexTypes:    $this->complexTypes,
            enumTypes:       array_values($this->enumTypes),
            typeDefinitions: $this->typeDefinitions,
            functions:       $this->functions,
        );

        $container = new EntityContainer(
            name:            $this->containerName,
            entitySets:      $this->entitySets,
            singletons:      $this->singletons,
            functionImports: $this->functionImports,
        );

        return new Edmx(
            version:         $this->version,
            references:      $this->references,
            schemas:         [$this->namespace => $schema],
            entityContainer: $container,
        );
    }

    private function assertNotBuilt(): void
    {
        if ($this->built) {
            throw new \LogicException('EdmBuilder has already been built and must not be mutated.');
        }
    }
}
