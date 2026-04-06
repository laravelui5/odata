<?php

declare(strict_types=1);

use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Fixtures\AbstractEntitySetServiceRegistry;
use LaravelUi5\OData\Tests\TestCase;

/**
 * Tier-4 HTTP round-trip tests for AbstractEntitySet.
 *
 * Verifies that an AbstractEntitySet-based custom entity set works end-to-end
 * through the OData engine: $metadata, service document, entity set queries,
 * single entity access, $filter, $select, $orderby, $count.
 */
uses(TestCase::class)
    ->beforeEach(function () {
        $this->withExceptionHandling();

        $this->app->instance(
            ODataServiceRegistryInterface::class,
            new AbstractEntitySetServiceRegistry(),
        );

        \LaravelUi5\OData\Fixtures\Models\Flight::insert([
            ['origin' => 'lhr', 'destination' => 'lax', 'gate' => 1, 'duration' => 41100.0],
            ['origin' => 'sfo', 'destination' => 'lax', 'gate' => 2, 'duration' => 2133.0],
            ['origin' => 'lhr', 'destination' => 'jfk', 'gate' => 3, 'duration' => 3600.0],
        ]);

        \LaravelUi5\OData\Fixtures\Models\Passenger::insert([
            ['name' => 'Alice', 'flight_id' => 1],
            ['name' => 'Bob',   'flight_id' => 1],
            ['name' => 'Carol', 'flight_id' => 2],
            ['name' => 'Dave',  'flight_id' => 3],
        ]);
    });

// ── $metadata ────────────────────────────────────────────────────────────────

describe('AbstractEntitySet $metadata', function () {
    it('exposes the auto-generated entity type in $metadata', function () {
        $response = $this->get('/odata/$metadata');

        $response->assertStatus(200);

        $xml = $response->streamedContent();
        expect($xml)->toContain('Name="FlightSummary"')
            ->and($xml)->toContain('Name="FlightSummaries"')
            ->and($xml)->toContain('Name="origin"')
            ->and($xml)->toContain('Name="flight_count"')
            ->and($xml)->toContain('Name="passenger_count"')
            ->and($xml)->toContain('Type="Edm.String"')
            ->and($xml)->toContain('Type="Edm.Int32"');
    });

    it('declares the correct key property', function () {
        $response = $this->get('/odata/$metadata');

        $xml = $response->streamedContent();
        expect($xml)->toContain('<PropertyRef Name="origin"/>');
    });
});

// ── Service document ─────────────────────────────────────────────────────────

it('lists FlightSummaries in the service document', function () {
    $response = $this->get('/odata/');

    $response->assertStatus(200);

    $doc = json_decode($response->streamedContent(), true);
    $names = array_column($doc['value'], 'name');

    expect($names)->toContain('FlightSummaries');
});

// ── Entity set collection ────────────────────────────────────────────────────

describe('AbstractEntitySet collection queries', function () {
    it('returns aggregated rows from the SQL query', function () {
        $response = $this->get('/odata/FlightSummaries');

        $response->assertStatus(200);

        $data = json_decode($response->streamedContent(), true);
        $rows = $data['value'];

        // 2 origins: lhr (2 flights, 3 passengers) and sfo (1 flight, 1 passenger)
        expect($rows)->toHaveCount(2);

        $byOrigin = array_column($rows, null, 'origin');

        expect($byOrigin['lhr']['flight_count'])->toBe(2)
            ->and($byOrigin['lhr']['passenger_count'])->toBe(3)
            ->and($byOrigin['sfo']['flight_count'])->toBe(1)
            ->and($byOrigin['sfo']['passenger_count'])->toBe(1);
    });

    it('supports $select', function () {
        $response = $this->get('/odata/FlightSummaries?$select=origin,flight_count');

        $response->assertStatus(200);

        $data = json_decode($response->streamedContent(), true);
        $row = $data['value'][0];

        expect($row)->toHaveKey('origin')
            ->and($row)->toHaveKey('flight_count')
            ->and($row)->not->toHaveKey('passenger_count');
    });

    it('supports $filter', function () {
        $response = $this->get("/odata/FlightSummaries?\$filter=origin eq 'sfo'");

        $response->assertStatus(200);

        $data = json_decode($response->streamedContent(), true);

        expect($data['value'])->toHaveCount(1)
            ->and($data['value'][0]['origin'])->toBe('sfo');
    });

    it('supports $orderby', function () {
        $response = $this->get('/odata/FlightSummaries?$orderby=origin desc');

        $response->assertStatus(200);

        $data = json_decode($response->streamedContent(), true);
        $origins = array_column($data['value'], 'origin');

        expect($origins)->toBe(['sfo', 'lhr']);
    });

    it('supports $top', function () {
        $response = $this->get('/odata/FlightSummaries?$top=1');

        $response->assertStatus(200);

        $data = json_decode($response->streamedContent(), true);

        expect($data['value'])->toHaveCount(1);
    });

    it('supports inline $count', function () {
        $response = $this->get('/odata/FlightSummaries?$count=true');

        $response->assertStatus(200);

        $data = json_decode($response->streamedContent(), true);

        expect($data)->toHaveKey('@odata.count')
            ->and($data['@odata.count'])->toBe(2);
    });
});

// ── Single entity ────────────────────────────────────────────────────────────

describe('AbstractEntitySet single entity', function () {
    it('supports single entity access by key', function () {
        $response = $this->get("/odata/FlightSummaries('lhr')");

        $response->assertStatus(200);

        $data = json_decode($response->streamedContent(), true);

        expect($data['origin'])->toBe('lhr')
            ->and($data['flight_count'])->toBe(2)
            ->and($data['passenger_count'])->toBe(3);
    });

    it('returns 404 for non-existent key', function () {
        $response = $this->get("/odata/FlightSummaries('zzz')");

        $response->assertStatus(404);
    });
});
