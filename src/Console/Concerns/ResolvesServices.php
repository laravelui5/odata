<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Console\Concerns;

use Composer\Autoload\ClassLoader;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use ReflectionClass;

/**
 * Resolves the target services for the cache commands: every registry service, plus any
 * named via `--class=FQCN1,FQCN2` (route-composed / bound services that are deliberately
 * NOT in the registry). Deduped by class. Prints an error and returns null on a bad entry.
 *
 * Services that live in a package are dropped — see {@see rejectVendored()}. Both commands
 * derive their target directory from the service class's own location, so neither may reach
 * into `vendor/`, and doing the filtering here means neither can forget to.
 */
trait ResolvesServices
{
    /**
     * @return list<ODataServiceInterface>|null  null = a --class entry was invalid (already reported)
     */
    protected function resolveServices(ODataServiceRegistryInterface $registry): ?array
    {
        $services = [];
        $seen     = [];

        foreach ($registry->services() as $service) {
            $services[] = $service;
            $seen[ltrim($service::class, '\\')] = true;
        }

        $option = (string) ($this->option('class') ?? '');

        foreach (array_filter(array_map('trim', explode(',', $option))) as $fqcn) {
            $fqcn = ltrim($fqcn, '\\');

            if (isset($seen[$fqcn])) {
                continue; // already provided by the registry
            }

            if (!class_exists($fqcn)) {
                $this->error("Class not found: {$fqcn}");
                return null;
            }

            $instance = app($fqcn);

            if (!$instance instanceof ODataServiceInterface) {
                $this->error("{$fqcn} does not implement ODataServiceInterface.");
                return null;
            }

            $services[] = $instance;
            $seen[$fqcn] = true;
        }

        return $this->rejectVendored($services);
    }

    /**
     * Drop services whose class lives in the composer vendor directory.
     *
     * The cache directory is derived from the service class's own location
     * (`dirname(classFile)/Edm`), so a packaged service would have its cache written *into*
     * `vendor/` — where nothing is version-controlled, the next `composer install` silently
     * reverts it, and the promise `odata:cache` prints in production ("the generated Edm/
     * cache is committed to version control and deployed as-is") cannot hold. `odata:clear`
     * is worse still: it would delete a package's cache outright, recoverable only by
     * reinstalling the package — and nothing in the resulting error points there.
     *
     * A package that wants a shipped cache generates it in its own build and commits it to
     * its own repository.
     *
     * @param  list<ODataServiceInterface>  $services
     * @return list<ODataServiceInterface>
     */
    protected function rejectVendored(array $services): array
    {
        $vendorDir = $this->vendorDir();

        if ($vendorDir === null) {
            return $services;
        }

        $kept = [];

        foreach ($services as $service) {
            $file = (new ReflectionClass($service))->getFileName();

            if (is_string($file) && str_starts_with($file, $vendorDir . DIRECTORY_SEPARATOR)) {
                // Announced, never silent: a skipped service is one whose schema
                // stays on the cold path, and that is worth knowing.
                $this->info(sprintf('Skipped (lives in a package): %s', $service::class));
                continue;
            }

            $kept[] = $service;
        }

        return $kept;
    }

    /** The composer vendor directory, located via the autoloader that owns it. */
    protected function vendorDir(): ?string
    {
        if (!class_exists(ClassLoader::class)) {
            return null;
        }

        $loaderFile = (new ReflectionClass(ClassLoader::class))->getFileName();

        // vendor/composer/ClassLoader.php → vendor
        return is_string($loaderFile) ? dirname($loaderFile, 2) : null;
    }
}
