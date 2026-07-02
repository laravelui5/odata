<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures;

use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;

/**
 * Test fixture service exposing {@see CustomOptionFlights} — a custom entity set
 * that reads a custom query option. The custom binding is auto-registered by
 * discoverCustomEntitySet(), so no registerBindings() is needed.
 */
final class CustomOptionService extends ODataService
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
        $this->discoverCustomEntitySet(CustomOptionFlights::class);

        return $builder->namespace('Test.Ns');
    }
}
