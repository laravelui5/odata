<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures;

use LaravelUi5\OData\Fixtures\Models\Flight;
use LaravelUi5\OData\Fixtures\Models\Passenger;
use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;

/**
 * Test fixture: a discoverModel service mounted on a NON-`/odata` prefix, declaring
 * its own mount by overriding route() and endpoint().
 *
 * Exercises OData::forService() route composition (the registry-independent seam) and
 * the mount-declaration contract: with the mount declared on both methods, path-stripping
 * (route()) AND the self-referential URLs — @odata.context / @odata.nextLink (endpoint())
 * — follow the bound prefix instead of the default `/odata`.
 */
final class BoundMountFlightService extends ODataService
{
    public function serviceUri(): string
    {
        return '';
    }

    public function namespace(): string
    {
        return 'Test.Ns';
    }

    /** Declare the bound mount — path-stripping. */
    public function route(): string
    {
        return 'alt';
    }

    /** Declare the bound mount — the @odata.context / @odata.nextLink service root. */
    public function endpoint(): string
    {
        return url('alt') . '/';
    }

    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        $this->discoverModel(Flight::class);
        $this->discoverModel(Passenger::class);

        return $builder->namespace('Test.Ns');
    }
}
