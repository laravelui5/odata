<?php

declare(strict_types=1);

namespace LaravelUi5\OData;

use LaravelUi5\OData\Edm\Container\EntitySet;
use LaravelUi5\OData\Service\Builder\EdmBuilder;
use LaravelUi5\OData\Service\Builder\ResolverMapBuilder;
use LaravelUi5\OData\Service\Builder\RuntimeSchemaBuilder;
use LaravelUi5\OData\Service\Cache\EdmxLoader;
use LaravelUi5\OData\Service\Cache\ResolverMapLoader;
use LaravelUi5\OData\Service\Contracts\CustomEntitySetInterface;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaBuilderInterface;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaInterface;
use LaravelUi5\OData\Edm\Property\NavigationProperty;
use LaravelUi5\OData\Service\Contracts\VirtualExpandResolverInterface;
use LaravelUi5\OData\Service\Discovery\ModelDiscovery;
use LaravelUi5\OData\Service\Resolver\ResolverMap;

/**
 * Base OData service implementation.
 *
 * Subclasses override configure(), registerBindings(), and bindFunctions()
 * to declare their EDM structure, wire entity set resolvers, and bind
 * function/singleton resolvers. The runtime schema is built lazily on the
 * first call to schema() and cached for the lifetime of the instance.
 *
 * Example:
 *
 *   class PartnerService extends ODataService
 *   {
 *       public function serviceUri(): string  { return 'partners'; }
 *       public function namespace(): string   { return 'Partners.Data'; }
 *
 *       protected function configure(EdmBuilderInterface $b): EdmBuilderInterface
 *       {
 *           $this->discoverModel(Partner::class);
 *           return $b->namespace('Partners.Data');
 *       }
 *
 *       protected function registerBindings(ResolverMapBuilder $map): void
 *       {
 *           // Discovered models are auto-registered. Add manual bindings:
 *           $c = $map->getEdmx()->getEntityContainer();
 *           $map->sql($c->getEntitySet('ValueHelp'), 'value_help_view');
 *       }
 *
 *       protected function bindFunctions(RuntimeSchemaBuilderInterface $b): void
 *       {
 *           // Bind function imports and singletons here.
 *       }
 *   }
 */
class ODataService implements ODataServiceInterface
{
    private ?RuntimeSchemaInterface $cachedSchema = null;
    private ?ModelDiscovery $discovery = null;

    /** @var list<class-string<CustomEntitySetInterface>> */
    private array $customEntitySets = [];

    public function __construct(
        private readonly string $serviceUriValue = '',
        private readonly string $namespaceValue = '',
    ) {}

    // ── ODataServiceInterface ───────────────────────────────────────────────

    public function serviceUri(): string
    {
        return $this->serviceUriValue;
    }

    public function namespace(): string
    {
        return $this->namespaceValue;
    }

    public function cachedMetadataXMLPath(): ?string
    {
        return null;
    }

    public function endpoint(): string
    {
        $prefix = rtrim(config('odata.prefix', 'odata'), '/');
        $uri    = $this->serviceUri();
        $route  = ($uri === '') ? $prefix : $prefix . '/' . $uri;

        return url($route) . '/';
    }

    public function route(): string
    {
        $prefix = rtrim(config('odata.prefix', 'odata'), '/');
        $uri    = $this->serviceUri();

        return ($uri === '') ? $prefix : $prefix . '/' . $uri;
    }

    public function schema(): RuntimeSchemaInterface
    {
        if ($this->cachedSchema === null) {
            // ── Warm path: cached Edmx + ResolverMap ────────────────────
            $edmx        = EdmxLoader::forService($this);
            $resolverMap = ResolverMapLoader::forService($this);

            if ($edmx !== null && $resolverMap !== null) {
                $runtimeBuilder = new RuntimeSchemaBuilder($edmx);
                $resolverMap->applyTo($runtimeBuilder);
                $this->bindFunctions($runtimeBuilder);
                $this->cachedSchema = $runtimeBuilder->build();

                return $this->cachedSchema;
            }

            // ── Cold path: build from configure() + discovery ───────────
            $builder = (new EdmBuilder())->version(config('odata.version', '4.0'));
            $builder = $this->configure($builder);

            // Register custom entity types first so discovery and the builder
            // can wire virtual navigation properties.
            $this->applyCustomEntitySets($builder);

            if ($this->discovery !== null) {
                $this->applyVirtualExpandsToDiscovery();
                $this->discovery->apply($builder, $this->namespace());
            }

            // Wire virtual expands on entity types NOT managed by discovery
            // (manually defined types). Discovery-managed types are handled
            // by applyVirtualExpandsToDiscovery() above.
            $this->applyVirtualExpandsToBuilder($builder);

            $edmx = $builder->build();

            // Build the ResolverMap from registerBindings() + discovery + custom entity sets
            $mapBuilder = new ResolverMapBuilder($edmx);

            if ($this->discovery !== null) {
                $this->discovery->registerOnMap($mapBuilder);
            }

            $this->applyCustomEntitySetBindings($mapBuilder);
            $this->registerBindings($mapBuilder);
            $resolverMap = $mapBuilder->build();

            $runtimeBuilder = new RuntimeSchemaBuilder($edmx);
            $resolverMap->applyTo($runtimeBuilder);
            $this->bindFunctions($runtimeBuilder);
            $this->cachedSchema = $runtimeBuilder->build();
        }

        return $this->cachedSchema;
    }

    /**
     * Return the ResolverMap for this service (used by odata:cache).
     *
     * Forces a cold-path schema build if not already cached.
     */
    public function resolverMap(): ResolverMap
    {
        // Ensure schema() has run so the map is built
        $this->schema();

        // Rebuild the map (schema() doesn't store it separately)
        $edmx = $this->cachedSchema->getEdmx();
        $mapBuilder = new ResolverMapBuilder($edmx);

        if ($this->discovery !== null) {
            $this->discovery->registerOnMap($mapBuilder);
        }

        $this->applyCustomEntitySetBindings($mapBuilder);
        $this->registerBindings($mapBuilder);

        return $mapBuilder->build();
    }

    // ── Extension hooks ─────────────────────────────────────────────────────

    /**
     * Populate the EdmBuilder with entity types, entity sets, functions, etc.
     *
     * Subclasses must override this and call $builder->namespace() before
     * returning. Use $this->discoverModel() to auto-discover Eloquent models.
     */
    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        return $builder->namespace($this->namespace());
    }

    /**
     * Register entity set resolver bindings.
     *
     * Discovered models are already registered as EloquentBindings.
     * Override this to add manual bindings for SQL views, custom sources, etc.
     * Bindings are serialized by odata:cache for the warm boot path.
     */
    protected function registerBindings(ResolverMapBuilder $map): void
    {
        // Default: no manual bindings beyond discovered models.
    }

    /**
     * Bind function import and singleton resolvers.
     *
     * These are not cached — they run on every boot (both cold and warm).
     * Override this to bind FunctionResolverInterface and SingletonResolverInterface.
     */
    protected function bindFunctions(RuntimeSchemaBuilderInterface $builder): void
    {
        // Default: no functions or singletons.
    }

    /**
     * Register an Eloquent model for auto-discovery.
     *
     * Call this in configure() to have the model's columns, key, and
     * relationships automatically mapped to OData entity types and sets.
     * An EloquentBinding is auto-registered in the ResolverMap.
     */
    protected function discoverModel(string $modelClass): static
    {
        $this->discovery ??= new ModelDiscovery();
        $this->discovery->add($modelClass);

        return $this;
    }

    /**
     * Register a custom entity set with colocated type definition and resolver.
     *
     * Call this in configure(). The entity type and set are added to the Edm,
     * and a CustomBinding is auto-registered in the ResolverMap — no manual
     * registerBindings() wiring needed.
     *
     * @param class-string<CustomEntitySetInterface> $resolverClass
     */
    protected function discoverCustomEntitySet(string $resolverClass): static
    {
        $this->customEntitySets[] = $resolverClass;

        return $this;
    }

    /**
     * Apply accumulated custom entity set registrations to the builder.
     */
    private function applyCustomEntitySets(EdmBuilderInterface $builder): void
    {
        $namespace = $this->namespace();

        foreach ($this->customEntitySets as $resolverClass) {
            $instance   = new $resolverClass();
            $entityType = $instance->entityType($namespace);
            $setName    = $instance->entitySetName();

            $builder->addEntityType($entityType);
            $builder->addEntitySet(new EntitySet($setName, $entityType));
        }
    }

    /**
     * Pass virtual expand declarations to discovery so it can wire
     * navigation properties on parent entity types in Pass 2.
     */
    private function applyVirtualExpandsToDiscovery(): void
    {
        $namespace = $this->namespace();

        foreach ($this->customEntitySets as $resolverClass) {
            $instance = new $resolverClass();

            if (!($instance instanceof VirtualExpandResolverInterface)) {
                continue;
            }

            $targetType    = $instance->entityType($namespace);
            $targetSetName = $instance->entitySetName();

            foreach ($instance->expandsOn() as $parentTypeName => $navName) {
                $this->discovery->addVirtualExpand(
                    $parentTypeName,
                    $navName,
                    $targetType,
                    $targetSetName,
                );
            }
        }
    }

    /**
     * Wire virtual expands directly on the builder for entity types that
     * are NOT managed by discovery (manually defined in configure()).
     *
     * Discovery-managed types are handled by applyVirtualExpandsToDiscovery().
     * This method skips types that discovery already knows about to avoid
     * duplicate navigation properties.
     */
    private function applyVirtualExpandsToBuilder(EdmBuilderInterface $builder): void
    {
        $namespace       = $this->namespace();
        $discoveredTypes = $this->discovery?->getDiscoveredTypeNames() ?? [];

        foreach ($this->customEntitySets as $resolverClass) {
            $instance = new $resolverClass();

            if (!($instance instanceof VirtualExpandResolverInterface)) {
                continue;
            }

            $targetType    = $instance->entityType($namespace);
            $targetSetName = $instance->entitySetName();

            foreach ($instance->expandsOn() as $parentTypeName => $navName) {
                // Skip types managed by discovery — they're handled in Pass 2
                if (in_array($parentTypeName, $discoveredTypes, true)) {
                    continue;
                }

                $builder->injectNavigationProperty(
                    $parentTypeName,
                    new NavigationProperty(
                        name: $navName,
                        targetType: $targetType,
                        isCollection: true,
                    ),
                    $targetSetName,
                );
            }
        }
    }

    /**
     * Register custom entity set bindings on the resolver map.
     */
    private function applyCustomEntitySetBindings(ResolverMapBuilder $map): void
    {
        $container = $map->getEdmx()->getEntityContainer();

        foreach ($this->customEntitySets as $resolverClass) {
            $instance = new $resolverClass();
            $setName  = $instance->entitySetName();
            $set      = $container->getEntitySet($setName);

            if ($set !== null) {
                $map->custom($set, $resolverClass);
            }
        }
    }
}
