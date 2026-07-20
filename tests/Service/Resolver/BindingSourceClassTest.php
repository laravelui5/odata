<?php

declare(strict_types=1);

use LaravelUi5\OData\Fixtures\Models\Flight;
use LaravelUi5\OData\Service\Resolver\CustomBinding;
use LaravelUi5\OData\Service\Resolver\EloquentBinding;
use LaravelUi5\OData\Service\Resolver\SqlBinding;
use LaravelUi5\OData\Service\Resolver\SqlSourceBinding;

/**
 * `ResolverBindingInterface::getSourceClass()` — the authored class-string a consumer
 * reflects class attributes on (permissions, capabilities, annotations).
 *
 * The key contract: it returns the **authored** source, NOT the runtime resolver — the
 * Eloquent **model** (not the generic `EloquentEntitySetResolver`), the custom/source class
 * otherwise, and `null` for a raw table/view that has no authored class.
 */
it('returns the Eloquent model for a discoverModel binding', function () {
    expect((new EloquentBinding(Flight::class))->getSourceClass())->toBe(Flight::class);
});

it('returns the resolver class for a custom binding', function () {
    $binding = new CustomBinding('App\\OData\\BillableProjects');
    expect($binding->getSourceClass())->toBe('App\\OData\\BillableProjects');
});

it('returns the source class for a source binding', function () {
    $binding = new SqlSourceBinding('App\\OData\\PartnerColleagues');
    expect($binding->getSourceClass())->toBe('App\\OData\\PartnerColleagues');
});

it('returns null for a raw table binding (no authored class)', function () {
    expect((new SqlBinding('flights'))->getSourceClass())->toBeNull();
});
