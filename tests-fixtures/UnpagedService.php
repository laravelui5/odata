<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures;

use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;

/** Test service: one set the server never pages, one it pages as configured (ROADMAP OP48). */
final class UnpagedService extends ODataService
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
        $this->discoverCustomEntitySet(UnpagedFlights::class);
        $this->discoverCustomEntitySet(PagedFlights::class);

        return $builder->namespace('Test.Ns');
    }
}
