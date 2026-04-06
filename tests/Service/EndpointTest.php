<?php

use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Tests\TestCase;

uses(TestCase::class);

it('resolves default odata service', function () {
    $service = app(ODataServiceRegistryInterface::class)->resolve('');
    expect($service)
        ->toBeInstanceOf(ODataService::class)
        ->and($service->endpoint())
        ->toBe('http://localhost/odata/');
});