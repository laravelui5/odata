<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Fixtures;

use LaravelUi5\OData\Fixtures\Models\AnnotatedAirport;
use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;

/**
 * Test fixture for annotation discovery HTTP round-trip tests.
 *
 * Discovers the AnnotatedAirport model which carries vocabulary annotations
 * (#[Description], #[Label], #[Hidden], #[SelectionFields], #[LineItem]).
 */
final class AnnotationDiscoveryService extends ODataService
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
        $this->discoverModel(AnnotatedAirport::class);

        return $builder->namespace('Test.Ns');
    }
}
