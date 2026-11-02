<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Discovery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
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
use LaravelUi5\OData\Edm\Type\TypeFacets;
use LaravelUi5\OData\Service\Contracts\ColumnFacetResolverInterface;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataEntity;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Service\Contracts\ColumnAnnotationResolverInterface;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataAnnotate;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataComputed;
use LaravelUi5\OData\Vocabularies\Core\V1\Computed;
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

    private readonly ColumnFacetResolverInterface $facetResolver;

    private readonly ColumnAnnotationResolverInterface $annotationResolver;

    public function __construct(
        ?ColumnFacetResolverInterface      $facetResolver = null,
        ?ColumnAnnotationResolverInterface $annotationResolver = null,
    ) {
        $this->attributeReader    = new AttributeReader();
        $this->facetResolver      = $facetResolver ?? app(ColumnFacetResolverInterface::class);
        $this->annotationResolver = $annotationResolver ?? app(ColumnAnnotationResolverInterface::class);
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
        $typeName = $entityAttr->name ?? $ref->getShortName();
        $entitySetName = $entityAttr->entitySet ?? Str::plural($typeName);

        self::assertUnique($this->typeNames, $modelClass, $typeName, 'entity type');
        self::assertUnique($this->entitySetNames, $modelClass, $entitySetName, 'entity set');

        $this->typeNames[$modelClass] = $typeName;
        $this->entitySetNames[$modelClass] = $entitySetName;

        // Discover columns
        $table = $model->getTable();
        $columns = Schema::getColumns($table);
        $casts = $model->getCasts();
        $keyName = $model->getKeyName();

        $properties = [];
        $keyProps = [];
        if (!self::serializes($model, $keyName)) {
            throw new \LogicException(sprintf(
                'The key "%s" of %s is never serialized ($hidden or $visible), so no entity of type "%s" could be read. '
                . 'Keep the key serializable, or serve the model through a custom entity set.',
                $keyName, $modelClass, $typeName,
            ));
        }

        $annotated = self::readAnnotateAttributes($ref);

        foreach ($columns as $column) {
            $colName = $column['name'];

            // What the model never serializes ($hidden, $visible) is not declared either.
            if (!self::serializes($model, $colName)) {
                continue;
            }

            // Determine OData type: casts override DB type
            $primitiveType = isset($casts[$colName])
                ? (self::mapCastType($casts[$colName]) ?? self::mapColumnType($column['type_name']))
                : self::mapColumnType($column['type_name']);

            $property = new Property(
                name: $colName,
                type: new PrimitiveType($primitiveType),
                facets: $this->resolveFacets($modelClass, $column, $casts[$colName] ?? null, $primitiveType, $colName === $keyName),
                annotations: self::mergeAnnotations(
                    $this->annotationResolver->annotations($modelClass, $colName),
                    $annotated[$colName] ?? [],
                ),
            );
            unset($annotated[$colName]);

            $properties[] = $property;

            if ($colName === $keyName) {
                $keyProps[] = $property;
            }
        }

        foreach (ComputedProperties::of($modelClass) as $name => $computed) {
            $properties[] = $this->computedProperty($model, $modelClass, $typeName, $computed, $properties, $annotated[$name] ?? []);
            unset($annotated[$name]);
        }

        if ($annotated !== []) {
            throw new \LogicException(sprintf(
                '#[ODataAnnotate] on %s names "%s", which entity type "%s" does not declare (no such column, or one the '
                . 'model never serializes).',
                $modelClass, implode('", "', array_keys($annotated)), $typeName,
            ));
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

            if (!self::serializes($model, $method->getName())) {
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

            // Polymorphic relations stay out, the whole family, before the regular arms:
            // MorphTo extends BelongsTo and MorphToMany extends BelongsToMany, so the
            // instanceof checks below would wire them. Their join runs over a type
            // discriminator plus an id, which no CSDL ReferentialConstraint can state —
            // even where the target is fixed, $metadata would describe half a relation.
            // Model the edge explicitly instead (custom entity set, virtual expand).
            if ($result instanceof MorphTo || $result instanceof MorphToMany || $result instanceof MorphOneOrMany) {
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

            $navProps[] = new NavigationProperty(
                name: $method->getName(),
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

    /**
     * The facets of a discovered column: the schema's, then the bound
     * {@see ColumnFacetResolverInterface}. A key property is never nullable, whatever either says.
     *
     * Returns null when the result says nothing beyond the spec's defaults, so an
     * unconstrained property serializes exactly as before.
     *
     * @param class-string<Model>                                     $modelClass
     * @param array{name: string, type_name: string, type: string, nullable: bool} $column
     */
    private function resolveFacets(
        string           $modelClass,
        array            $column,
        ?string          $cast,
        EdmPrimitiveType $type,
        bool             $isKey,
    ): ?TypeFacets {
        $facets = $this->facetResolver->resolve($modelClass, $column['name'], $cast, self::schemaFacets($column, $type));

        if ($isKey) {
            $facets = $facets->withNullable(false);
        }

        self::assertFacetsFit($modelClass, $column['name'], $type, $facets);

        return $facets->isDefault() ? null : $facets;
    }

    /**
     * Derive the type facets of a discovered column from its schema.
     *
     * The column decides `Nullable`, `Precision`/`Scale` on a `decimal(p,s)` and `MaxLength` on a
     * length-bearing character type. A facet is taken only when the final Edm type can carry it,
     * so a cast to another type never inherits a length or a scale that belongs to the column's.
     *
     * @param array{type_name: string, type: string, nullable: bool} $column
     */
    private static function schemaFacets(array $column, EdmPrimitiveType $type): TypeFacets
    {
        $typeName = strtolower($column['type_name']);
        $declared = strtolower($column['type']);

        $maxLength = null;
        $precision = null;
        $scale     = null;

        if ($type === EdmPrimitiveType::Decimal
            && in_array($typeName, ['decimal', 'numeric'], true)
            && preg_match('/\(\s*(\d+)\s*(?:,\s*(\d+)\s*)?\)/', $declared, $m) === 1
        ) {
            $precision = (int) $m[1];
            $scale     = isset($m[2]) ? (int) $m[2] : 0;
        }

        if ($type === EdmPrimitiveType::String
            && in_array($typeName, ['varchar', 'char', 'nvarchar', 'nchar', 'bpchar', 'character varying', 'character'], true)
            && preg_match('/\(\s*(\d+|max)\s*\)/', $declared, $m) === 1
        ) {
            $maxLength = $m[1] === 'max' ? PHP_INT_MAX : (int) $m[1];
        }

        return new TypeFacets(
            nullable:  (bool) $column['nullable'],
            maxLength: $maxLength,
            precision: $precision,
            scale:     $scale,
        );
    }

    /**
     * Refuse facets the declared type cannot carry, loudly at schema build — an invalid facet
     * would otherwise reach `$metadata` and be rejected (or misread) by every client.
     *
     * `Scale` belongs to `Edm.Decimal` and may not exceed `Precision`; `Precision` belongs to
     * `Edm.Decimal` and the temporal types (fractional seconds); `MaxLength` to `Edm.String` and
     * `Edm.Binary`.
     */
    private static function assertFacetsFit(string $modelClass, string $column, EdmPrimitiveType $type, TypeFacets $facets): void
    {
        $where = sprintf('%s::$%s (%s)', $modelClass, $column, $type->value);

        if ($facets->getScale() !== null && $type !== EdmPrimitiveType::Decimal) {
            throw new \LogicException("Scale is only valid on Edm.Decimal, not on {$where}.");
        }

        $temporal = [EdmPrimitiveType::DateTimeOffset, EdmPrimitiveType::TimeOfDay, EdmPrimitiveType::Duration];
        if ($facets->getPrecision() !== null && $type !== EdmPrimitiveType::Decimal && !in_array($type, $temporal, true)) {
            throw new \LogicException("Precision is only valid on Edm.Decimal and temporal types, not on {$where}.");
        }

        if ($facets->getMaxLength() !== null && !in_array($type, [EdmPrimitiveType::String, EdmPrimitiveType::Binary], true)) {
            throw new \LogicException("MaxLength is only valid on Edm.String and Edm.Binary, not on {$where}.");
        }

        if ($facets->getScale() !== null && $facets->getScale() >= 0 && $facets->getPrecision() !== null
            && $facets->getScale() > $facets->getPrecision()
        ) {
            throw new \LogicException(sprintf(
                'Scale %d exceeds Precision %d on %s.',
                $facets->getScale(),
                $facets->getPrecision(),
                $where,
            ));
        }
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
            'date', 'immutable_date' => EdmPrimitiveType::Date,
            'datetime', 'timestamp', 'immutable_datetime'
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

    /**
     * A `#[ODataComputed]` declaration as a property: its type and facets, `Core.Computed`, and what
     * `#[ODataAnnotate]` says about it. Its name must be free and serializable — the value travels as an
     * attribute of the model under that name.
     *
     * @param class-string<Model>      $modelClass
     * @param list<Property>           $columns     the properties declared so far
     * @param list<AnnotationInterface> $annotations from `#[ODataAnnotate]`
     */
    private function computedProperty(
        Model         $model,
        string        $modelClass,
        string        $typeName,
        ODataComputed $computed,
        array         $columns,
        array         $annotations,
    ): Property {
        foreach ($columns as $column) {
            if ($column->getName() === $computed->name) {
                throw new \LogicException(sprintf(
                    '#[ODataComputed] on %s declares "%s", which entity type "%s" already has as a column.',
                    $modelClass, $computed->name, $typeName,
                ));
            }
        }
        if (!self::serializes($model, $computed->name)) {
            throw new \LogicException(sprintf(
                '#[ODataComputed] on %s declares "%s", which the model never serializes ($hidden or $visible).',
                $modelClass, $computed->name,
            ));
        }

        $facets = $computed->facets();
        self::assertFacetsFit($modelClass, $computed->name, $computed->type, $facets);

        return new Property(
            name: $computed->name,
            type: new PrimitiveType($computed->type),
            facets: $facets->isDefault() ? null : $facets,
            annotations: self::mergeAnnotations([new Computed()], $annotations),
        );
    }

    /**
     * The model's `#[ODataAnnotate]` attributes, by the property they name.
     *
     * @return array<string, list<AnnotationInterface>>
     */
    private static function readAnnotateAttributes(ReflectionClass $ref): array
    {
        $byProperty = [];
        foreach ($ref->getAttributes(ODataAnnotate::class) as $attribute) {
            $annotate = $attribute->newInstance();
            foreach ($annotate->annotations as $annotation) {
                $byProperty[$annotate->property][] = $annotation->toAnnotation();
            }
        }

        return $byProperty;
    }

    /**
     * The bound resolver's annotations for a column, then the model's own; on the same term and qualifier the
     * model's attribute wins — it is local to one model.
     *
     * @param list<AnnotationInterface|TypedAnnotationInterface> $fromResolver
     * @param list<AnnotationInterface>                          $fromAttribute
     * @return list<AnnotationInterface>
     */
    private static function mergeAnnotations(array $fromResolver, array $fromAttribute): array
    {
        $merged = [];
        foreach ([$fromResolver, $fromAttribute] as $source) {
            foreach ($source as $annotation) {
                $annotation = $annotation instanceof TypedAnnotationInterface ? $annotation->toAnnotation() : $annotation;
                $merged[$annotation->getTerm() . '#' . ($annotation->getQualifier() ?? '')] = $annotation;
            }
        }

        return array_values($merged);
    }

    /**
     * Does `toArray()` carry this attribute or relation? Eloquent's own rule: a non-empty `$visible` admits only what
     * it names, and `$hidden` removes from that. What the model never serializes is not declared.
     */
    private static function serializes(Model $model, string $name): bool
    {
        $visible = $model->getVisible();

        return ($visible === [] || in_array($name, $visible, true))
            && !in_array($name, $model->getHidden(), true);
    }

    /**
     * Two models must not resolve to the same entity type or entity set: the second would replace the first, and
     * the service would fail on every request with a message about a missing resolver.
     *
     * @param array<class-string<Model>, string> $taken model class => name already given
     */
    private static function assertUnique(array $taken, string $modelClass, string $name, string $what): void
    {
        $other = array_search($name, $taken, true);
        if ($other !== false && $other !== $modelClass) {
            throw new \LogicException(sprintf(
                'Both %s and %s resolve to the %s "%s". Name one of them with #[ODataEntity(name:, entitySet:)].',
                $other, $modelClass, $what, $name,
            ));
        }
    }
}
