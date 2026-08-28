<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Discovery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LaravelUi5\OData\Edm\Container\EntitySet;
use LaravelUi5\OData\Edm\Container\NavigationPropertyBinding;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Service\Builder\ResolverMapBuilder;
use LaravelUi5\OData\Edm\Property\NavigationProperty;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataEntity;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataIgnore;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataNavigation;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataProperty;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

final class ModelDiscovery
{
    /** @var array<class-string<Model>, string> modelClass => short class name placeholder */
    private array $modelClasses = [];

    /** @var array<class-string<Model>, EntityType> Pass 1 results (without nav props) */
    private array $bareTypes = [];

    /** @var array<class-string<Model>, list<Property>> */
    private array $properties = [];

    /** @var array<class-string<Model>, list<Property>> key properties */
    private array $keys = [];

    /** @var array<class-string<Model>, string> modelClass => entity type name */
    private array $typeNames = [];

    /** @var array<class-string<Model>, string> modelClass => entity set name */
    private array $entitySetNames = [];

    /** @var array<string, class-string<Model>> entitySetName => modelClass */
    private array $entitySetMap = [];

    /** @var array<class-string<Model>, list<\LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface>> */
    private array $classAnnotations = [];

    /** @var list<array{typeName: string, navName: string, targetType: EntityTypeInterface, targetSetName: string}> */
    private array $virtualExpands = [];

    private readonly AttributeReader $attributeReader;

    public function __construct()
    {
        $this->attributeReader = new AttributeReader();
    }

    public function add(string $modelClass): void
    {
        $this->modelClasses[$modelClass] = true;
    }

    /**
     * Register a virtual navigation property to be added to a discovered entity type.
     *
     * Called before apply() so that Pass 2 can include the virtual nav prop
     * alongside discovered Eloquent relations.
     */
    public function addVirtualExpand(
        string              $typeName,
        string              $navName,
        EntityTypeInterface $targetType,
        string              $targetSetName,
    ): void {
        $this->virtualExpands[] = [
            'typeName'      => $typeName,
            'navName'       => $navName,
            'targetType'    => $targetType,
            'targetSetName' => $targetSetName,
        ];
    }

    /**
     * Two-pass discovery: build types, then wire navigation properties.
     */
    public function apply(EdmBuilderInterface $builder, string $namespace): void
    {
        // Pass 1: discover structural properties and bare entity types
        foreach (array_keys($this->modelClasses) as $modelClass) {
            $this->discoverModel($modelClass, $namespace);
        }

        // Pass 2: discover relationships, rebuild types with nav props, register on builder
        foreach (array_keys($this->modelClasses) as $modelClass) {
            $ref = new ReflectionClass($modelClass);
            $model = self::instantiate($ref);
            $navProps = $this->discoverRelationships($model, $ref, $namespace);

            // Append virtual navigation properties targeting this entity type
            $typeName = $this->typeNames[$modelClass];
            $virtualBindings = [];

            foreach ($this->virtualExpands as $ve) {
                if ($ve['typeName'] === $typeName) {
                    $navProps[] = new NavigationProperty(
                        name: $ve['navName'],
                        targetType: $ve['targetType'],
                        isCollection: true,
                    );
                    $virtualBindings[] = new NavigationPropertyBinding(
                        $ve['navName'],
                        $ve['targetSetName'],
                    );
                }
            }

            $entityType = new EntityType(
                namespace: $namespace,
                name: $typeName,
                key: $this->keys[$modelClass],
                declaredProperties: $this->properties[$modelClass],
                declaredNavigationProperties: $navProps,
                annotations: $this->classAnnotations[$modelClass] ?? [],
            );

            $navBindings = [];
            foreach ($navProps as $navProp) {
                $targetName = $navProp->getTargetType()->getName();
                // Find the entity set for this target type (Eloquent relations)
                foreach ($this->typeNames as $mc => $tn) {
                    if ($tn === $targetName && isset($this->entitySetNames[$mc])) {
                        $navBindings[] = new NavigationPropertyBinding(
                            $navProp->getName(),
                            $this->entitySetNames[$mc],
                        );
                        break;
                    }
                }
            }

            // Add virtual expand bindings (target set is a custom entity set, not discovered)
            $navBindings = array_merge($navBindings, $virtualBindings);

            $entitySet = new EntitySet(
                name: $this->entitySetNames[$modelClass],
                entityType: $entityType,
                navigationPropertyBindings: $navBindings,
            );

            $builder->addEntityType($entityType);
            $builder->addEntitySet($entitySet);

            $this->entitySetMap[$this->entitySetNames[$modelClass]] = $modelClass;
        }
    }

    /**
     * @return array<string, class-string<Model>> entitySetName => modelClass
     */
    public function getEntitySetMap(): array
    {
        return $this->entitySetMap;
    }

    /**
     * @return list<string> discovered entity type names
     */
    public function getDiscoveredTypeNames(): array
    {
        return array_values($this->typeNames);
    }

    /**
     * Register discovered models as EloquentBindings on a ResolverMapBuilder.
     *
     * Called after apply() so that entitySetMap is populated.
     */
    public function registerOnMap(ResolverMapBuilder $map): void
    {
        $container = $map->getEdmx()->getEntityContainer();

        foreach ($this->entitySetMap as $entitySetName => $modelClass) {
            $set = $container->getEntitySet($entitySetName);
            if ($set !== null) {
                $map->eloquent($set, $modelClass);
            }
        }
    }

    /**
     * Pass 1: inspect columns, casts, key — build properties and bare type.
     */
    private function discoverModel(string $modelClass, string $namespace): void
    {
        $ref = new ReflectionClass($modelClass);
        $model = self::instantiate($ref);

        // Resolve names (with attribute overrides)
        $entityAttr = $this->readEntityAttribute($ref);
        $typeName = $entityAttr?->name ?? $ref->getShortName();
        $entitySetName = $entityAttr?->entitySet ?? Str::plural($typeName);

        $this->typeNames[$modelClass] = $typeName;
        $this->entitySetNames[$modelClass] = $entitySetName;

        // Discover columns
        $table = $model->getTable();
        $columns = Schema::getColumns($table);
        $casts = $model->getCasts();
        $keyName = $model->getKeyName();

        $properties = [];
        $keyProps = [];

        foreach ($columns as $column) {
            $colName = $column['name'];

            // Check for #[ODataIgnore] on the model property (if it exists)
            if ($ref->hasProperty($colName) && $this->hasIgnoreAttribute($ref->getProperty($colName))) {
                continue;
            }

            // Determine OData type: casts override DB type
            $primitiveType = isset($casts[$colName])
                ? (self::mapCastType($casts[$colName]) ?? self::mapColumnType($column['type_name']))
                : self::mapColumnType($column['type_name']);

            // Check for #[ODataProperty] overrides
            $propAttr = $ref->hasProperty($colName)
                ? $this->readPropertyAttribute($ref->getProperty($colName))
                : null;

            if ($propAttr?->type !== null) {
                $enumCase = EdmPrimitiveType::tryFrom($propAttr->type);
                if ($enumCase !== null) {
                    $primitiveType = $enumCase;
                }
            }

            $propName = $propAttr?->name ?? $colName;

            // Read vocabulary annotations from PHP attributes on the model property
            $propAnnotations = $ref->hasProperty($colName)
                ? $this->attributeReader->readProperty($ref->getProperty($colName))
                : [];

            $property = new Property(
                name: $propName,
                type: new PrimitiveType($primitiveType),
                annotations: $propAnnotations,
            );

            $properties[] = $property;

            if ($colName === $keyName) {
                $keyProps[] = $property;
            }
        }

        $this->properties[$modelClass] = $properties;
        $this->keys[$modelClass] = $keyProps;

        // Read class-level vocabulary annotations for the final EntityType in Pass 2
        $this->classAnnotations[$modelClass] = $this->attributeReader->readClass($ref);

        // Store bare type for nav prop target references in Pass 2
        $this->bareTypes[$modelClass] = new EntityType(
            namespace: $namespace,
            name: $typeName,
            key: $keyProps,
            declaredProperties: $properties,
            annotations: $this->classAnnotations[$modelClass],
        );
    }

    /**
     * Pass 2: discover relationships and build navigation properties.
     *
     * @return list<NavigationProperty>
     */
    private function discoverRelationships(Model $model, ReflectionClass $ref, string $namespace): array
    {
        $navProps = [];

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Only own methods, no magic, no parameters
            if ($method->class !== $ref->getName()) {
                continue;
            }
            if (str_starts_with($method->getName(), '__')) {
                continue;
            }
            if ($method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            // Check for #[ODataIgnore]
            if ($method->getAttributes(ODataIgnore::class) !== []) {
                continue;
            }

            // A declared return type that cannot be a Relation settles it
            // without invoking anything. Probing by calling is unavoidable
            // for untyped methods, but calling a `fullKey(): string` on an
            // unhydrated model is how discovery used to emit warnings from
            // code that has nothing to do with OData — the try/catch below
            // does not help, because a PHP warning is not a Throwable.
            if (! $this->mayReturnRelation($method)) {
                continue;
            }

            // Try calling the method to get a Relation
            try {
                $result = $method->invoke($model);
            } catch (\Throwable) {
                continue;
            }

            if (!$result instanceof Relation) {
                continue;
            }

            // Only support specific relation types
            $isCollection = match (true) {
                $result instanceof HasMany, $result instanceof BelongsToMany => true,
                $result instanceof BelongsTo, $result instanceof HasOne => false,
                default => null,
            };

            if ($isCollection === null) {
                continue;
            }

            $relatedClass = get_class($result->getRelated());

            // Only wire if the target model was also discovered
            if (!isset($this->bareTypes[$relatedClass])) {
                continue;
            }

            // Check for #[ODataNavigation] name override
            $navAttrs = $method->getAttributes(ODataNavigation::class);
            $navName = $navAttrs !== []
                ? ($navAttrs[0]->newInstance()->name ?? $method->getName())
                : $method->getName();

            $navProps[] = new NavigationProperty(
                name: $navName,
                targetType: $this->bareTypes[$relatedClass],
                isCollection: $isCollection,
            );
        }

        return $navProps;
    }

    /**
     * Can this method possibly return an Eloquent {@see Relation}?
     *
     * Answered from the declared return type alone, so the decision costs no
     * invocation:
     *
     *  - **no return type** → unknown, must be probed (the common Eloquent
     *    idiom `public function orders() { return $this->hasMany(...); }`)
     *  - **`Relation` or a subclass** → yes
     *  - **anything else** — `string`, `int`, `bool`, `array`, `void`, a
     *    union, an unrelated class — → no
     *
     * Discovery walks *every* public no-arg method of a model, including ones
     * that merely read state. Invoking those on the unhydrated probe instance
     * can dereference a null relation and emit a PHP warning attributed to a
     * class that has nothing to do with OData; the surrounding `try/catch`
     * cannot suppress it, because a warning is not a `Throwable`.
     */
    private function mayReturnRelation(ReflectionMethod $method): bool
    {
        $type = $method->getReturnType();

        if ($type === null) {
            return true;
        }

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return false;
        }

        $name = $type->getName();

        return $name === Relation::class || is_subclass_of($name, Relation::class);
    }

    /**
     * Instantiate a model for inspection — **through its constructor**.
     *
     * This used to be `newInstanceWithoutConstructor()`, which silently broke
     * cast discovery. Since Laravel 11 the idiomatic declaration is a
     * `protected function casts(): array` method, and Eloquent merges its return
     * into `$this->casts` **in the constructor**. Skipping the constructor left
     * `getCasts()` returning only the auto-added key cast, so the cast-override
     * branch below was dead for every model written the modern way — including
     * everything `laravel new` scaffolds.
     *
     * It went unnoticed because the usual column types happen to map correctly
     * from the database alone (`datetime` → DateTimeOffset, `varchar` → String).
     * The first divergence is a boolean: SQLite and MySQL store it as
     * `tinyint(1)`, `tinyint` sits in the integer branch of
     * {@see mapColumnType()}, and the column surfaced as `Edm.Int32` while the
     * payload — serialized from a properly constructed model — carried `true`.
     * Contract and wire disagreed.
     *
     * Eloquent's constructor takes no required arguments, boots the model and
     * initializes its traits, all of which discovery wants anyway.
     */
    private static function instantiate(ReflectionClass $ref): Model
    {
        return $ref->newInstance();
    }

    private static function mapColumnType(string $typeName): EdmPrimitiveType
    {
        return match (true) {
            in_array($typeName, ['string', 'varchar', 'char', 'text', 'tinytext', 'mediumtext', 'longtext', 'enum', 'set'], true)
                => EdmPrimitiveType::String,
            in_array($typeName, ['integer', 'int', 'tinyint', 'smallint', 'mediumint'], true)
                => EdmPrimitiveType::Int32,
            in_array($typeName, ['bigint'], true)
                => EdmPrimitiveType::Int64,
            in_array($typeName, ['float', 'double', 'real'], true)
                => EdmPrimitiveType::Double,
            in_array($typeName, ['decimal', 'numeric'], true)
                => EdmPrimitiveType::Decimal,
            in_array($typeName, ['boolean', 'bool'], true)
                => EdmPrimitiveType::Boolean,
            $typeName === 'date'
                => EdmPrimitiveType::Date,
            in_array($typeName, ['datetime', 'timestamp'], true)
                => EdmPrimitiveType::DateTimeOffset,
            $typeName === 'time'
                => EdmPrimitiveType::TimeOfDay,
            in_array($typeName, ['blob', 'binary', 'varbinary'], true)
                => EdmPrimitiveType::Binary,
            in_array($typeName, ['json', 'jsonb'], true)
                => EdmPrimitiveType::String,
            in_array($typeName, ['uuid', 'guid'], true)
                => EdmPrimitiveType::Guid,
            default => EdmPrimitiveType::String,
        };
    }

    private static function mapCastType(string $cast): ?EdmPrimitiveType
    {
        $baseCast = str_contains($cast, ':') ? substr($cast, 0, (int) strpos($cast, ':')) : $cast;

        return match ($baseCast) {
            'integer', 'int' => EdmPrimitiveType::Int32,
            'float', 'double' => EdmPrimitiveType::Double,
            'decimal' => EdmPrimitiveType::Decimal,
            'boolean', 'bool' => EdmPrimitiveType::Boolean,
            'date' => EdmPrimitiveType::Date,
            'datetime', 'timestamp', 'immutable_date', 'immutable_datetime'
                => EdmPrimitiveType::DateTimeOffset,
            'string' => EdmPrimitiveType::String,
            'array', 'json', 'collection', 'object'
                => EdmPrimitiveType::String,
            default => null,
        };
    }

    private function readEntityAttribute(ReflectionClass $ref): ?ODataEntity
    {
        $attrs = $ref->getAttributes(ODataEntity::class);
        return $attrs !== [] ? $attrs[0]->newInstance() : null;
    }

    private function readPropertyAttribute(\ReflectionProperty $prop): ?ODataProperty
    {
        $attrs = $prop->getAttributes(ODataProperty::class);
        return $attrs !== [] ? $attrs[0]->newInstance() : null;
    }

    private function hasIgnoreAttribute(\ReflectionProperty $prop): bool
    {
        return $prop->getAttributes(ODataIgnore::class) !== [];
    }
}
