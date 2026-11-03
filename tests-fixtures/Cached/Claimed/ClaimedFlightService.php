<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures\Cached\Claimed;

use LaravelUi5\OData\Fixtures\Models\Flight;
use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;

/**
 * Test fixture: a service that keeps its generated `$metadata` document outside `resources/edmx/` and claims it for
 * the cache commands (OP47), alone in its directory as odata:cache requires (OP01).
 */
final class ClaimedFlightService extends ODataService
{
    public static function documentPath(): string
    {
        return sys_get_temp_dir() . '/odata-claimed-' . getmypid() . '/Metadata.xml';
    }

    public function serviceUri(): string
    {
        return '';
    }

    public function namespace(): string
    {
        return 'Claimed.Ns';
    }

    public function cachedMetadataXMLPath(): string
    {
        return self::documentPath();
    }

    public function cachesMetadataXML(): bool
    {
        return true;
    }

    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        $this->discoverModel(Flight::class);

        return $builder->namespace('Claimed.Ns');
    }
}
