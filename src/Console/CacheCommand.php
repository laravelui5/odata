<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelUi5\OData\Console\Concerns\ResolvesServices;
use LaravelUi5\OData\Service\Cache\EdmxWriter;
use LaravelUi5\OData\Service\Cache\ResolverMapWriter;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use ReflectionClass;

class CacheCommand extends Command
{
    use ResolvesServices;

    protected $signature = 'odata:cache {--class= : Comma-separated FQCNs of additional OData services to cache (route-composed/bound services not in the registry)}';

    protected $description = 'Generate cached Edm PHP classes for OData services — the registry, plus any --class services (dev only)';

    public function handle(ODataServiceRegistryInterface $registry): int
    {
        if (app()->environment('production', 'staging')) {
            $this->error('odata:cache must not be run in production or staging.');
            $this->error('The generated Edm/ cache is committed to version control and deployed as-is.');
            $this->error('Run odata:cache on your development machine, commit the result, then deploy.');

            return self::FAILURE;
        }

        $services = $this->resolveServices($registry);

        if ($services === null) {
            return self::FAILURE;
        }

        if ($services === []) {
            $this->info('Nothing to cache — every registered service lives in a package.');

            return self::SUCCESS;
        }

        // Pre-pass: each service must own its cache directory. Two services sharing a
        // namespace would overwrite each other's Edm/ — and the warm path would then serve
        // the wrong schema. Fail loud BEFORE writing anything, rather than silently.
        $claimedBy = [];
        foreach ($services as $service) {
            $dir = dirname((new ReflectionClass($service))->getFileName()) . '/Edm';
            if (isset($claimedBy[$dir])) {
                $this->error(sprintf(
                    'Cache directory collision: %s and %s both map to %s.',
                    $service::class,
                    $claimedBy[$dir],
                    $dir,
                ));
                $this->error('Each OData service must live in its own namespace/directory.');

                return self::FAILURE;
            }
            $claimedBy[$dir] = $service::class;
        }

        // ── Pass 1: build everything, write nothing ─────────────────────
        //
        // Deleting as we go was the second half of the empty-map disaster: a
        // service that threw part-way left the ones before it rewritten and the
        // ones after it with no cache at all. Building first means a failure
        // anywhere leaves every existing cache exactly as it was.
        $built = [];

        foreach ($services as $service) {
            $reflected = new ReflectionClass($service);

            // Both out of one cold pass — never from `schema()`, which memoises and
            // prefers the warm path, so on a consumer that already has a cache it
            // would hand back the previous Edmx to be written out again.
            [$edmx, $resolverMap] = $service->buildForCache();

            // `schema()` runs the RuntimeSchemaBuilder, which refuses a schema
            // whose declared sets have no binding. Calling it here keeps that
            // check in the build pass — before anything is deleted — without a
            // second guard of our own: a branch that cannot fire is dead code.
            $service->schema();

            $built[] = [$service, $reflected, $edmx, $resolverMap];
        }

        // ── Pass 2: replace the caches ──────────────────────────────────
        $fs = new Filesystem();

        foreach ($built as [$service, $reflected, $edmx, $resolverMap]) {
            $outputDir = dirname($reflected->getFileName()) . '/Edm';
            $namespace = $reflected->getNamespaceName() . '\\Edm';

            if ($fs->isDirectory($outputDir)) {
                $fs->deleteDirectory($outputDir);
                $this->info("Cleared {$outputDir}");
            }

            $writer = new EdmxWriter(
                edmx: $edmx,
                outputDir: $outputDir,
                namespace: $namespace,
                output: fn(string $line) => $this->info($line),
            );

            $writer->write();

            ResolverMapWriter::write($service, $resolverMap);

            $this->info("Cached: {$reflected->getName()}");
        }

        // Refresh autoloader so the newly generated Edm classes are discoverable.
        // Skipped under tests — it shells out to composer and would regenerate the
        // package's own autoloader.
        if (!app()->runningUnitTests()) {
            $this->info('Refreshing autoloader...');
            passthru('composer dump-autoload 2>&1');
        }

        $this->info('OData schema cached successfully.');

        return self::SUCCESS;
    }
}
