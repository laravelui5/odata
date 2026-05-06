<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures;

use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\Builder\ResolverMapBuilder;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;

/**
 * Test fixture: ODataService that exposes one AbstractEntitySet
 * with a backed-enum column.
 */
final class EnumService extends ODataService
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
