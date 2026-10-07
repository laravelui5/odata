<?php

declare(strict_types=1);

use LaravelUi5\OData\Fixtures\FlightServiceRegistry;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Tests\TestCase;

/*
 * OP13: `odata.version` is the one answer for `$metadata`'s Version and the
 * OData-Version header of every response — not eleven string literals.
 */

uses(TestCase::class);

beforeEach(function () {
    $this->withExceptionHandling();
    $this->app->instance(ODataServiceRegistryInterface::class, new FlightServiceRegistry());
});

it('answers 4.0 by default', function () {
    expect($this->get('/odata/Flights')->headers->get('OData-Version'))->toBe('4.0');
});

it('carries a configured version into $metadata and every response header', function () {
    config(['odata.version' => '4.01']);

    $metadata = $this->get('/odata/$metadata');
    $set      = $this->get('/odata/Flights');
    $root     = $this->get('/odata/');
    $batch    = $this->postJson('/odata/$batch', ['requests' => [['id' => '1', 'method' => 'GET', 'url' => 'Flights']]]);

    expect($metadata->streamedContent())->toContain('Version="4.01"')
        ->and($metadata->headers->get('OData-Version'))->toBe('4.01')
        ->and($set->headers->get('OData-Version'))->toBe('4.01')
        ->and($root->headers->get('OData-Version'))->toBe('4.01')
        ->and($batch->headers->get('OData-Version'))->toBe('4.01');
});
