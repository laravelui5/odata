<?php

declare(strict_types=1);

use LaravelUi5\OData\Fixtures\CustomOptionServiceRegistry;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Tests\TestCase;

/**
 * Tier-4 HTTP tests: an OData custom query option (`?origin=lhr`) must reach an
 * entity set's query() on BOTH request paths — a direct GET and an inner request
 * inside a `$batch`. The batch case is the regression guard: custom options live
 * only on the inner request's URL, never on the outer `$batch` envelope.
 */
uses(TestCase::class)
    ->beforeEach(function () {
        $this->withExceptionHandling();

        $this->app->instance(
            ODataServiceRegistryInterface::class,
            new CustomOptionServiceRegistry(),
        );

        \LaravelUi5\OData\Fixtures\Models\Flight::insert([
            ['origin' => 'lhr', 'destination' => 'lax', 'gate' => 1, 'duration' => 41100.0],
            ['origin' => 'sfo', 'destination' => 'lax', 'gate' => 2, 'duration' => 2133.0],
            ['origin' => 'lhr', 'destination' => 'jfk', 'gate' => 3, 'duration' => 3600.0],
        ]);
    });

it('applies a custom query option on a direct GET', function () {
    $response = $this->get('/odata/CustomOptionFlights?origin=lhr');

    $response->assertStatus(200);
    $value = json_decode($response->streamedContent(), true)['value'];

    expect($value)->toHaveCount(2)
        ->and(collect($value)->pluck('origin')->unique()->all())->toBe(['lhr']);
});

it('returns all rows when the custom option is absent', function () {
    $response = $this->get('/odata/CustomOptionFlights');

    $response->assertStatus(200);
    $value = json_decode($response->streamedContent(), true)['value'];

    expect($value)->toHaveCount(3);
});

it('applies a custom query option on an inner $batch request', function () {
    $batch = [
        'requests' => [
            ['id' => '0', 'method' => 'GET', 'url' => 'CustomOptionFlights?origin=lhr'],
        ],
    ];

    $response = $this->postJson('/odata/$batch', $batch);

    $response->assertStatus(200);
    $inner = json_decode($response->streamedContent(), true)['responses'][0];

    expect($inner['status'])->toBe(200)
        ->and($inner['body']['value'])->toHaveCount(2)
        ->and(collect($inner['body']['value'])->pluck('origin')->unique()->all())->toBe(['lhr']);
});
