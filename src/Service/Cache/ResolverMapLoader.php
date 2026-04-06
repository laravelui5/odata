<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Cache;

use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Resolver\ResolverMap;
use ReflectionClass;

/**
 * Loads a cached ResolverMap from the generated Edm\ directory next to a service class.
 *
 * Convention: {ServiceClassNamespace}\Edm\ResolverMap with a static load() method.
 */
final class ResolverMapLoader
{
    /**
     * Attempt to load a cached ResolverMap for the given service.
     *
     * Returns null when no cache exists.
     */
    public static function forService(ODataServiceInterface $service): ?ResolverMap
    {
        // Check the file exists before class_exists() to avoid autoloader
        // errors when the cache directory has been cleared but the in-memory
        // classmap still references the deleted files.
        $refl = new ReflectionClass($service);
        $cacheFile = dirname($refl->getFileName()) . '/Edm/ResolverMap.php';
        if (!file_exists($cacheFile)) {
            return null;
        }

        $className = self::resolverMapClassName($service);

        if (!class_exists($className)) {
            return null;
        }

        return $className::load();
    }

    /**
     * Derive the FQCN of the cached ResolverMap class from a service instance.
     *
     * Convention: {ServiceClassNamespace}\Edm\ResolverMap
     */
    public static function resolverMapClassName(ODataServiceInterface $service): string
    {
        $refl = new ReflectionClass($service);
        return $refl->getNamespaceName() . '\\Edm\\ResolverMap';
    }
}
