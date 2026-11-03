<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures\Cached\Enums;

use LaravelUi5\OData\Fixtures\PassengerColours;

use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\Builder\ResolverMapBuilder;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;

/**
 * Test fixture: EnumService alone in its directory, as odata:cache requires (one service per directory, OP01).
 */
final class CachedEnumService extends ODataService
{
    public function serviceUri(): string
    {
        return '';
    }

    public function namespace(): string
    {
        return 'Test.Ns';
    }

    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        $this->discoverCustomEntitySet(PassengerColours::class);

        return $builder->namespace('Test.Ns');
    }

    protected function registerBindings(ResolverMapBuilder $map): void
    {
    }
}
