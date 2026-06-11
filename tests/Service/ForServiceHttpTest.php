<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use LaravelUi5\OData\Fixtures\BoundMountFlightService;
use LaravelUi5\OData\Fixtures\Models\Flight;
use LaravelUi5\OData\Http\Controller\OData;
use LaravelUi5\OData\Tests\TestCase;

/**
 * HTTP round-trip tests for OData::forService() — the registry-independent seam.
 *
 * Composes a bound-service route on a NON-`/odata` prefix with its own pipeline (the
 * documented "alternative client" pattern, e.g. `/excel` + auth.basic), and verifies
 * both that it serves and that the bound mount is honored in self-referential URLs.
 */
uses(TestCase::class)
    ->beforeEach(function () {
        $this->withExceptionHandling();

        // A service-bound route on its own prefix — no registry involved.
        Route::any('alt/{path?}', fn (Request $request, ?string $path = null) =>
            app(OData::class)->forService($request, app(BoundMountFlightService::class))
        )->where('path', '.*');

        Flight::insert([
            ['origin' => 'lhr', 'destination' => 'lax', 'gate' => 1, 'duration' => 41100.0],
            ['origin' => 'sfo', 'destination' => 'lax', 'gate' => 2, 'duration' => 2133.0],
            ['origin' => 'lhr', 'destination' => 'jfk', 'gate' => 3, 'duration' => 3600.0],
        ]);
    });

it('serves $metadata for a bound service on a custom prefix', function () {
    $response = $this->get('/alt/$metadata');

    $response->assertStatus(200);
    expect($response->streamedContent())->toContain('Name="Flights"');
});

it('serves an entity collection through forService (no registry)', function () {
    $response = $this->get('/alt/Flights');

    $response->assertStatus(200);

    $data = json_decode($response->streamedContent(), true);
    expect($data['value'])->toHaveCount(3);
});

it('honors the bound mount in @odata.nextLink (the paging contract)', function () {
    // Page size 1 forces an @odata.nextLink. It MUST point at /alt, never /odata —
    // otherwise a downstream client (Excel) pages into the wrong namespace.
    $response = $this->get('/alt/Flights', ['Prefer' => 'odata.maxpagesize=1']);

    $response->assertStatus(200);

    $data = json_decode($response->streamedContent(), true);
    expect($data)->toHaveKey('@odata.nextLink');
    expect($data['@odata.nextLink'])->toContain('/alt/')
        ->and($data['@odata.nextLink'])->not->toContain('/odata/');
});
