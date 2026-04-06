<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use LaravelUi5\OData\Service\Cache\EdmxWriter;
use LaravelUi5\OData\Service\Cache\ResolverMapWriter;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use ReflectionClass;

class CacheCommand extends Command
{
    protected $signature = 'odata:cache';

    protected $description = 'Generate cached Edm PHP classes for all registered OData services (dev only)';

    public function handle(ODataServiceRegistryInterface $registry): int
    {
        if (app()->environment('production', 'staging')) {
            $this->error('odata:cache must not be run in production or staging.');
            $this->error('The generated Edm/ cache is committed to version control and deployed as-is.');
            $this->error('Run odata:cache on your development machine, commit the result, then deploy.');

            return self::FAILURE;
        }

        $fs = new Filesystem();

        foreach ($registry->services() as $service) {
            $reflected = new ReflectionClass($service);
            $outputDir = dirname($reflected->getFileName()) . '/Edm';
            $namespace = $reflected->getNamespaceName() . '\\Edm';

            // Remove stale cache so schema() takes the cold path.
            if ($fs->isDirectory($outputDir)) {
                $fs->deleteDirectory($outputDir);
                $this->info("Cleared {$outputDir}");
            }

            $edmx = $service->schema()->getEdmx();

            $writer = new EdmxWriter(
                edmx: $edmx,
                outputDir: $outputDir,
                namespace: $namespace,
                output: fn(string $line) => $this->info($line),
            );

            $writer->write();

            $resolverMap = $service->resolverMap();
            ResolverMapWriter::write($service, $resolverMap);

            $this->info("Cached: {$reflected->getName()}");
        }

        // Refresh autoloader so the newly generated Edm classes are discoverable.
        $this->info('Refreshing autoloader...');
        passthru('composer dump-autoload 2>&1');

        $this->info('OData schema cached successfully.');

        return self::SUCCESS;
    }
}
