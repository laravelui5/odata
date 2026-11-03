<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Console\Concerns;

use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;

/**
 * The `$metadata` document the cache commands own: only a path under `resources/edmx/`. A service that points
 * `cachedMetadataXMLPath()` elsewhere ships its own document, which is served but never written or deleted (OP12).
 */
trait ManagesMetadataDocument
{
    protected function managedMetadataDocument(ODataServiceInterface $service): ?string
    {
        $path = $service->cachedMetadataXMLPath();

        return $path !== null && str_starts_with($path, resource_path('edmx') . DIRECTORY_SEPARATOR) ? $path : null;
    }
}
