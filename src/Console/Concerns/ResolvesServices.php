<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Console\Concerns;

use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;

/**
 * Resolves the target services for the cache commands: every registry service, plus any
 * named via `--class=FQCN1,FQCN2` (route-composed / bound services that are deliberately
 * NOT in the registry). Deduped by class. Prints an error and returns null on a bad entry.
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

        return $services;
    }
}
