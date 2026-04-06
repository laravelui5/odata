<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Console;

use Illuminate\Console\Command;
use LaravelUi5\OData\Service\Cache\EdmxLoader;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;

class ClearCommand extends Command
{
    protected $signature = 'odata:clear';

    protected $description = 'Remove cached Edm PHP classes for all registered OData services (dev only)';

    public function handle(ODataServiceRegistryInterface $registry): int
    {
        if (app()->environment('production', 'staging')) {
            $this->error('odata:clear must not be run in production or staging.');
            $this->error('The generated Edm/ cache is committed to version control and deployed as-is.');
            $this->error('Run odata:clear on your development machine only.');

            return self::FAILURE;
        }

        foreach ($registry->services() as $service) {
            $cacheDir = EdmxLoader::cacheDir($service);

            if (!is_dir($cacheDir)) {
                continue;
            }

            $this->deleteDirectory($cacheDir);
            $this->info("Cleared: {$cacheDir}");
        }

        $this->info('OData cache cleared.');

        return self::SUCCESS;
    }

    private function deleteDirectory(string $dir): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}
