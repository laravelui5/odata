<?php

declare(strict_types=1);

use LaravelUi5\OData\Fixtures\Models\Flight;
use LaravelUi5\OData\Fixtures\UnpagedServiceRegistry;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Tests\TestCase;

/**
 * ROADMAP OP48: a set marked UnpagedEntitySetInterface is never paged by the server — not by `pagination.default`,
 * not by `pagination.max`, not on a client's `Prefer: odata.maxpagesize` — and still honours `$top`. An unmarked set
 * is paged as before.
 */
uses(TestCase::class);

beforeEach(function () {
    $this->withExceptionHandling();
    $this->app->instance(ODataServiceRegistryInterface::class, new UnpagedServiceRegistry());
    config(['odata.pagination.default' => 5, 'odata.pagination.max' => 4]);

    Flight::insert(array_map(fn (int $i): array => ['origin' => "o{$i}", 'destination' => 'lax', 'gate' => $i, 'duration' => 1.0], range(1, 12)));
});

/** @return array{0: array<string, mixed>, 1: \Illuminate\Testing\TestResponse} */
function unpagedGet(TestCase $test, string $url, array $headers = []): array
{
    $response = $test->get($url, $headers);
    $response->assertStatus(200);

    return [json_decode($response->streamedContent(), true), $response];
}

it('delivers a marked set whole, whatever the server\'s default and maximum', function () {
    [$data, $response] = unpagedGet($this, '/odata/UnpagedFlights');

    expect($data['value'])->toHaveCount(12)
        ->and($data)->not->toHaveKey('@odata.nextLink')
        ->and($response->headers->has('Preference-Applied'))->toBeFalse();
});

it('declines a client\'s page-size preference on a marked set', function () {
    [$data, $response] = unpagedGet($this, '/odata/UnpagedFlights', ['Prefer' => 'odata.maxpagesize=3']);

    expect($data['value'])->toHaveCount(12)
        ->and($data)->not->toHaveKey('@odata.nextLink')
        ->and($response->headers->has('Preference-Applied'))->toBeFalse();
});

it('still honours $top on a marked set', function () {
    [$data] = unpagedGet($this, '/odata/UnpagedFlights?$top=2');

    expect($data['value'])->toHaveCount(2);
});

it('pages an unmarked set as configured', function () {
    [$data, $response] = unpagedGet($this, '/odata/PagedFlights');

    expect($data['value'])->toHaveCount(4)                                  // the default 5, clamped to the maximum 4
        ->and($data)->toHaveKey('@odata.nextLink')
        ->and($response->headers->get('Preference-Applied'))->toBe('odata.maxpagesize=4');
});
