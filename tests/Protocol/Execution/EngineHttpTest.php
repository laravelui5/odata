<?php

declare(strict_types=1);

use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Fixtures\FlightServiceRegistry;
use LaravelUi5\OData\Tests\TestCase;

/**
 * Tier-4 HTTP tests for the Protocol\Execution\Engine, exercised through
 * the full OData::handle() → Engine pipeline.
 *
 * Uses FlightService + FlightServiceRegistry as the test fixture.
 */
uses(TestCase::class)
    ->beforeEach(function () {
        $this->withExceptionHandling();

        $this->app->instance(
            ODataServiceRegistryInterface::class,
            new FlightServiceRegistry(),
        );

        \LaravelUi5\OData\Fixtures\Models\Flight::insert([
            ['origin' => 'lhr', 'destination' => 'lax', 'gate' => 1, 'duration' => 41100.0],
            ['origin' => 'sfo', 'destination' => 'lax', 'gate' => 2, 'duration' => 2133.0],
            ['origin' => 'jfk', 'destination' => 'ord', 'gate' => 3, 'duration' => 3600.0],
        ]);

        \LaravelUi5\OData\Fixtures\Models\Passenger::insert([
            ['name' => 'Alice', 'flight_id' => 1],
            ['name' => 'Bob',   'flight_id' => 1],
            ['name' => 'Carol', 'flight_id' => 2],
        ]);

        \LaravelUi5\OData\Fixtures\Models\Airport::insert([
            ['name' => 'Heathrow',             'code' => 'LHR'],
            ['name' => 'Los Angeles Intl',     'code' => 'LAX'],
            ['name' => 'San Francisco Intl',   'code' => 'SFO'],
        ]);

        // Pivot: flight_id → airport_id with role.
        // Pivot rows have their own auto-increment `id` to reproduce the
        // column-name collision edge case (pivot.id vs airports.id).
        \Illuminate\Support\Facades\DB::table('airport_flight')->insert([
            ['flight_id' => 1, 'airport_id' => 1, 'role' => 'origin'],      // LHR→LAX: Heathrow
            ['flight_id' => 1, 'airport_id' => 2, 'role' => 'destination'], // LHR→LAX: LAX
            ['flight_id' => 2, 'airport_id' => 3, 'role' => 'origin'],      // SFO→LAX: SFO
            ['flight_id' => 2, 'airport_id' => 2, 'role' => 'destination'], // SFO→LAX: LAX
        ]);
    });

// ── $metadata ─────────────────────────────────────────────────────────────────

it('GET /$metadata returns 200 with CSDL XML', function () {
    $response = $this->get('/odata/$metadata');

    $response->assertStatus(200);
    expect($response->headers->get('Content-Type'))->toContain('application/xml');
    expect($response->streamedContent())->toContain('edmx:Edmx')
        ->and($response->streamedContent())->toContain('Test.Ns')
        ->and($response->streamedContent())->toContain('Flight');
});

// ── Service document ──────────────────────────────────────────────────────────

it('GET / returns 200 service document JSON', function () {
    $response = $this->get('/odata/');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    $names = array_column($doc['value'], 'name');
    expect($doc)->toHaveKey('@odata.context')
        ->and($names)->toContain('Flights')
        ->and($names)->toContain('Passengers')
        ->and($names)->toContain('DefaultFlight');
});

// ── Entity set collection ─────────────────────────────────────────────────────

it('GET /Flights returns all rows', function () {
    $response = $this->get('/odata/Flights');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc)->toHaveKey('@odata.context')
        ->and($doc['@odata.context'])->toContain('$metadata#Flights')
        ->and($doc['value'])->toHaveCount(3);
});

it('GET /Flights with $top=2 returns two rows', function () {
    $response = $this->get('/odata/Flights?$top=2');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(2);
});

it('GET /Flights with $filter returns matching rows', function () {
    $response = $this->get('/odata/Flights?$filter=origin eq \'lhr\'');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(1)
        ->and($doc['value'][0]['origin'])->toBe('lhr');
});

it('GET /Flights with $skip=2 skips first two rows', function () {
    $response = $this->get('/odata/Flights?$skip=2');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(1);
});

it('GET /Flights with $top=1&$skip=1 returns second row only', function () {
    $response = $this->get('/odata/Flights?$top=1&$skip=1');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(1)
        ->and($doc['value'][0]['origin'])->toBe('sfo');
});

it('GET /Flights with $orderby=origin asc returns sorted rows', function () {
    $response = $this->get('/odata/Flights?$orderby=origin asc');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'][0]['origin'])->toBe('jfk')
        ->and($doc['value'][1]['origin'])->toBe('lhr')
        ->and($doc['value'][2]['origin'])->toBe('sfo');
});

it('GET /Flights with $orderby=origin desc returns reverse sorted rows', function () {
    $response = $this->get('/odata/Flights?$orderby=origin desc');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'][0]['origin'])->toBe('sfo')
        ->and($doc['value'][2]['origin'])->toBe('jfk');
});

it('GET /Flights with $filter and $orderby combined', function () {
    $response = $this->get('/odata/Flights?$filter=destination eq \'lax\'&$orderby=origin desc');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(2)
        ->and($doc['value'][0]['origin'])->toBe('sfo')
        ->and($doc['value'][1]['origin'])->toBe('lhr');
});

it('GET /Flights with $top=1&$orderby=origin asc returns first sorted row', function () {
    $response = $this->get('/odata/Flights?$top=1&$orderby=origin asc');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(1)
        ->and($doc['value'][0]['origin'])->toBe('jfk');
});

it('GET /Flights with $filter=origin ne returns non-matching rows', function () {
    $response = $this->get('/odata/Flights?$filter=origin ne \'lhr\'');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(2);
});

it('GET /Flights with $filter using gt on id', function () {
    $response = $this->get('/odata/Flights?$filter=id gt 1');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(2);
});

it('GET /Flights with $filter using and operator', function () {
    $response = $this->get('/odata/Flights?$filter=origin eq \'sfo\' and destination eq \'lax\'');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(1)
        ->and($doc['value'][0]['origin'])->toBe('sfo');
});

it('GET /Flights with $filter using or operator', function () {
    $response = $this->get('/odata/Flights?$filter=origin eq \'lhr\' or origin eq \'jfk\'');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(2);
});

it('GET /Flights with $filter using contains()', function () {
    $response = $this->get('/odata/Flights?$filter=contains(origin, \'hr\')');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(1)
        ->and($doc['value'][0]['origin'])->toBe('lhr');
});

it('GET /Flights with $filter using startswith()', function () {
    $response = $this->get('/odata/Flights?$filter=startswith(origin, \'sf\')');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(1)
        ->and($doc['value'][0]['origin'])->toBe('sfo');
});

it('GET /Flights with $filter using endswith()', function () {
    $response = $this->get('/odata/Flights?$filter=endswith(destination, \'ax\')');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(2);
});

it('GET /Flights with $filter using le and ge operators', function () {
    $response = $this->get('/odata/Flights?$filter=id ge 2 and id le 3');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(2);
});

it('GET /Flights with $filter using lt operator', function () {
    $response = $this->get('/odata/Flights?$filter=id lt 2');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(1)
        ->and($doc['value'][0]['id'])->toBe(1);
});

it('GET /Flights with combined $filter, $select, $orderby, $top, $skip', function () {
    $response = $this->get('/odata/Flights?$filter=destination eq \'lax\'&$select=origin&$orderby=origin desc&$top=1&$skip=0');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(1)
        ->and($doc['value'][0])->toBe(['origin' => 'sfo']);
});

it('GET /Flights with $count=true and all query options', function () {
    $response = $this->get('/odata/Flights?$filter=destination eq \'lax\'&$top=1&$count=true');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['@odata.count'])->toBe(2)
        ->and($doc['value'])->toHaveCount(1);
});

// ── $search ──────────────────────────────────────────────────────────────────

it('GET /Flights with $search=lhr returns matching rows', function () {
    $response = $this->get('/odata/Flights?$search=lhr');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(1)
        ->and($doc['value'][0]['origin'])->toBe('lhr');
});

it('GET /Flights with $search=lax returns rows where any string column matches', function () {
    $response = $this->get('/odata/Flights?$search=lax');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    // lax appears in destination of flight 1 and 2, and nowhere as origin
    expect($doc['value'])->toHaveCount(2);
});

it('GET /Flights with $search=nonexistent returns empty', function () {
    $response = $this->get('/odata/Flights?$search=nonexistent');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toBe([]);
});

it('GET /Passengers with $search=ali matches name (case-insensitive on SQLite)', function () {
    $response = $this->get('/odata/Passengers?$search=Ali');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(1)
        ->and($doc['value'][0]['name'])->toBe('Alice');
});

it('GET /Flights with $search and $filter combined', function () {
    $response = $this->get('/odata/Flights?$search=lax&$filter=origin eq \'lhr\'');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    // lhr origin AND lax in any string column → flight 1 (lhr→lax)
    expect($doc['value'])->toHaveCount(1)
        ->and($doc['value'][0]['origin'])->toBe('lhr');
});

// ── $compute ─────────────────────────────────────────────────────────────────

it('GET /Flights with $compute adds computed property to each row', function () {
    $response = $this->get("/odata/Flights?\$compute=concat(origin, ' to ', destination) as route");

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'][0])->toHaveKey('route')
        ->and($doc['value'][0]['route'])->toBe('lhr to lax');
});

it('GET /Flights with $compute arithmetic', function () {
    $response = $this->get('/odata/Flights?$compute=id add 100 as bigId');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'][0]['bigId'])->toBe(101)
        ->and($doc['value'][1]['bigId'])->toBe(102);
});

it('GET /Flights with $compute and $select shows both', function () {
    $response = $this->get("/odata/Flights?\$compute=concat(origin, '-', destination) as route&\$select=origin");

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'][0])->toHaveKey('origin')
        ->and($doc['value'][0])->toHaveKey('route')
        ->and($doc['value'][0]['route'])->toBe('lhr-lax');
});

// ── Response format ──────────────────────────────────────────────────────────

it('GET /Passengers with $filter on FK returns matching rows', function () {
    $response = $this->get('/odata/Passengers?$filter=flight_id eq 1');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(2)
        ->and($doc['value'][0]['name'])->toBe('Alice');
});

it('GET /Passengers with $select=name&$orderby=name desc', function () {
    $response = $this->get('/odata/Passengers?$select=name&$orderby=name desc');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(3)
        ->and($doc['value'][0])->toBe(['name' => 'Carol'])
        ->and($doc['value'][1])->toBe(['name' => 'Bob'])
        ->and($doc['value'][2])->toBe(['name' => 'Alice']);
});

it('GET /Flights with $filter that matches nothing returns empty collection', function () {
    $response = $this->get('/odata/Flights?$filter=origin eq \'xyz\'');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toBe([])
        ->and($doc)->toHaveKey('@odata.context');
});

it('GET /Flights with $top=0 returns empty collection', function () {
    $response = $this->get('/odata/Flights?$top=0');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toBe([]);
});

it('GET /Flights with $skip larger than total returns empty collection', function () {
    $response = $this->get('/odata/Flights?$skip=100');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toBe([]);
});

it('GET /Flights with $count=true and no matches returns count 0', function () {
    $response = $this->get('/odata/Flights?$filter=origin eq \'xyz\'&$count=true');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['@odata.count'])->toBe(0)
        ->and($doc['value'])->toBe([]);
});

it('GET /Flights(1) with $expand=passengers and $select=origin returns both', function () {
    $response = $this->get('/odata/Flights(1)?$expand=passengers&$select=origin');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc)->toHaveKey('origin')
        ->and($doc)->toHaveKey('passengers')
        ->and($doc)->not->toHaveKey('destination')
        ->and($doc['passengers'])->toHaveCount(2);
});

it('POST /$batch with function import inner request', function () {
    $response = $this->postJson('/odata/$batch', [
        'requests' => [
            ['id' => '1', 'method' => 'GET', 'url' => 'GetFlightCount()'],
            ['id' => '2', 'method' => 'GET', 'url' => 'Flights?$top=1'],
        ],
    ]);

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['responses'])->toHaveCount(2)
        ->and($doc['responses'][0]['body']['value'])->toBe(3)
        ->and($doc['responses'][1]['body']['value'])->toHaveCount(1);
});

it('GET /Flights(1)/passengers with $select=name returns only name', function () {
    $response = $this->get('/odata/Flights(1)/passengers?$select=name');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(2)
        ->and($doc['value'][0])->toHaveKey('name')
        ->and($doc['value'][0])->not->toHaveKey('flight_id');
});

it('GET /Flights(1)/passengers with $orderby=name desc', function () {
    $response = $this->get('/odata/Flights(1)/passengers?$orderby=name desc');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'][0]['name'])->toBe('Bob')
        ->and($doc['value'][1]['name'])->toBe('Alice');
});

it('GET /Flights(1)/passengers with $count=true returns filtered count', function () {
    $response = $this->get('/odata/Flights(1)/passengers?$count=true');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['@odata.count'])->toBe(2)
        ->and($doc['value'])->toHaveCount(2);
});

it('GET /Passengers(1) returns correct entity with all properties', function () {
    $response = $this->get('/odata/Passengers(1)');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc)->toHaveKey('@odata.context')
        ->and($doc['@odata.context'])->toContain('$metadata#Passengers/$entity')
        ->and($doc['id'])->toBe(1)
        ->and($doc['name'])->toBe('Alice')
        ->and($doc['flight_id'])->toBe(1);
});

it('GET /Flights returns @odata.context pointing to entity set', function () {
    $response = $this->get('/odata/Flights');
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['@odata.context'])->toBe('http://localhost/odata/$metadata#Flights');
});

it('GET /Flights(1) returns @odata.context pointing to entity', function () {
    $response = $this->get('/odata/Flights(1)');
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['@odata.context'])->toBe('http://localhost/odata/$metadata#Flights/$entity');
});

it('GET / service document has correct @odata.context', function () {
    $response = $this->get('/odata/');
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['@odata.context'])->toBe('http://localhost/odata/$metadata');
});

it('GET /Flights with $select=origin has context with select clause', function () {
    $response = $this->get('/odata/Flights?$select=origin');
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['@odata.context'])->toBe('http://localhost/odata/$metadata#Flights(origin)');
});

it('GET /Flights(1) with $select=origin has context with select and $entity', function () {
    $response = $this->get('/odata/Flights(1)?$select=origin');
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['@odata.context'])->toBe('http://localhost/odata/$metadata#Flights(origin)/$entity');
});

it('GET /$metadata includes all entity types and sets', function () {
    $response = $this->get('/odata/$metadata');
    $xml = $response->streamedContent();

    expect($xml)->toContain('Flight')
        ->and($xml)->toContain('Passenger')
        ->and($xml)->toContain('Flights')
        ->and($xml)->toContain('Passengers')
        ->and($xml)->toContain('GetFlightCount')
        ->and($xml)->toContain('DefaultFlight');
});

it('GET /$metadata returns valid XML', function () {
    $response = $this->get('/odata/$metadata');

    $doc = new \DOMDocument();
    $loaded = $doc->loadXML($response->streamedContent());

    expect($loaded)->toBeTrue();
});

it('GET /$metadata has correct content type', function () {
    $response = $this->get('/odata/$metadata');

    expect($response->headers->get('Content-Type'))->toContain('application/xml');
});

// ── HTTP method handling ─────────────────────────────────────────────────────

it('POST to entity set returns error (read-only)', function () {
    $response = $this->postJson('/odata/Flights', ['origin' => 'test']);

    // New engine only supports GET; POST should fail
    $response->assertStatus(400);
});

it('DELETE to entity returns error (read-only)', function () {
    $response = $this->delete('/odata/Flights(1)');

    $response->assertStatus(400);
});

it('PATCH to entity returns error (read-only)', function () {
    $response = $this->patch('/odata/Flights(1)', ['origin' => 'test']);

    $response->assertStatus(400);
});

// ── Multiple entity sets ─────────────────────────────────────────────────────

it('service document lists all entity sets and singletons with correct format', function () {
    $response = $this->get('/odata/');
    $doc = json_decode($response->streamedContent(), true);

    foreach ($doc['value'] as $entry) {
        expect($entry)->toHaveKeys(['name', 'kind', 'url']);
        expect($entry['kind'])->toBeIn(['EntitySet', 'Singleton']);
    }
});

// ── Batch edge cases ─────────────────────────────────────────────────────────

it('POST /$batch with empty requests array returns empty responses', function () {
    $response = $this->postJson('/odata/$batch', ['requests' => []]);

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['responses'])->toBe([]);
});

it('POST /$batch with missing id returns 400', function () {
    $response = $this->postJson('/odata/$batch', [
        'requests' => [
            ['method' => 'GET', 'url' => 'Flights'],
        ],
    ]);

    $response->assertStatus(400);
});

it('POST /$batch inner 404 does not abort batch', function () {
    $response = $this->postJson('/odata/$batch', [
        'requests' => [
            ['id' => '1', 'method' => 'GET', 'url' => 'Flights(999)'],
            ['id' => '2', 'method' => 'GET', 'url' => 'Flights(1)'],
        ],
    ]);

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['responses'][0]['status'])->toBe(404)
        ->and($doc['responses'][1]['status'])->toBe(200)
        ->and($doc['responses'][1]['body']['origin'])->toBe('lhr');
});

// ── Singleton edge cases ─────────────────────────────────────────────────────

it('GET /DefaultFlight has correct @odata.context', function () {
    $response = $this->get('/odata/DefaultFlight');
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['@odata.context'])->toBe('http://localhost/odata/$metadata#DefaultFlight');
});

// ── Function import edge cases ───────────────────────────────────────────────

it('GET /GetFlightCount() has correct @odata.context', function () {
    $response = $this->get('/odata/GetFlightCount()');
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['@odata.context'])->toBe('http://localhost/odata/$metadata#GetFlightCount');
});

it('GET /GetFlightsByOrigin with multiple calls returns consistent results', function () {
    $r1 = $this->get('/odata/GetFlightsByOrigin(origin=\'lhr\')');
    $r2 = $this->get('/odata/GetFlightsByOrigin(origin=\'lhr\')');

    $d1 = json_decode($r1->streamedContent(), true);
    $d2 = json_decode($r2->streamedContent(), true);

    expect($d1['value'])->toBe($d2['value']);
});

// ── Navigation path edge cases ───────────────────────────────────────────────

it('GET /Flights(1)/passengers has correct @odata.context', function () {
    $response = $this->get('/odata/Flights(1)/passengers');
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['@odata.context'])->toContain('$metadata#Passengers');
});

it('GET /Flights(999)/passengers returns empty collection (parent does not exist)', function () {
    $response = $this->get('/odata/Flights(999)/passengers');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toBe([]);
});

// ── $expand edge cases ───────────────────────────────────────────────────────

it('GET /Flights with $expand=passengers and $count=true', function () {
    $response = $this->get('/odata/Flights?$expand=passengers&$count=true');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['@odata.count'])->toBe(3)
        ->and($doc['value'][0])->toHaveKey('passengers');
});

it('GET /Flights with $expand=passengers and $top=1', function () {
    $response = $this->get('/odata/Flights?$expand=passengers&$top=1');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(1)
        ->and($doc['value'][0])->toHaveKey('passengers')
        ->and($doc['value'][0]['passengers'])->toHaveCount(2);
});

// ── Query option validation ──────────────────────────────────────────────────

it('GET /Flights with unknown $-prefixed option returns 400', function () {
    $response = $this->get('/odata/Flights?$hello=world');

    $response->assertStatus(400);
});

it('GET /Flights with non-$-prefixed option is accepted', function () {
    $response = $this->get('/odata/Flights?hello=world');

    $response->assertStatus(200);
});

it('GET /Flights with $apply returns 501 not implemented', function () {
    $response = $this->get('/odata/Flights?$apply=groupby((origin))');

    $response->assertStatus(501);
});

// ── Property value access ────────────────────────────────────────────────────

it('GET /Flights(1)/origin returns the property value as JSON', function () {
    $response = $this->get('/odata/Flights(1)/origin');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc)->toHaveKey('value')
        ->and($doc['value'])->toBe('lhr');
});

it('GET /Flights(1)/destination returns the property value', function () {
    $response = $this->get('/odata/Flights(1)/destination');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toBe('lax');
});

it('GET /Flights(1)/id returns the integer property', function () {
    $response = $this->get('/odata/Flights(1)/id');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toBe(1);
});

it('GET /Flights(1)/origin/$value returns raw text value', function () {
    $response = $this->get('/odata/Flights(1)/origin/$value');

    $response->assertStatus(200);
    expect($response->headers->get('Content-Type'))->toContain('text/plain')
        ->and($response->streamedContent())->toBe('lhr');
});

it('GET /Flights(999)/origin returns 404', function () {
    $response = $this->get('/odata/Flights(999)/origin');

    $response->assertStatus(404);
});

it('GET /Passengers(1)/name returns passenger name', function () {
    $response = $this->get('/odata/Passengers(1)/name');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toBe('Alice');
});

it('GET /Passengers(1)/name/$value returns raw passenger name', function () {
    $response = $this->get('/odata/Passengers(1)/name/$value');

    $response->assertStatus(200);
    expect($response->streamedContent())->toBe('Alice');
});

// ── $filter edge cases ───────────────────────────────────────────────────────

it('GET /Flights with $filter using parenthesized expression', function () {
    $response = $this->get('/odata/Flights?$filter=(origin eq \'lhr\' or origin eq \'sfo\') and destination eq \'lax\'');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(2);
});

it('GET /Flights with $filter id eq 1 returns single row', function () {
    $response = $this->get('/odata/Flights?$filter=id eq 1');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(1)
        ->and($doc['value'][0]['origin'])->toBe('lhr');
});

it('GET /Passengers with $filter name ne null returns all (none are null)', function () {
    $response = $this->get('/odata/Passengers?$filter=name ne null');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(3);
});

// ── Combined operations across entity sets ───────────────────────────────────

it('GET /Passengers with $expand=flight and $filter on flight_id', function () {
    $response = $this->get('/odata/Passengers?$expand=flight&$filter=flight_id eq 1');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(2)
        ->and($doc['value'][0]['flight']['origin'])->toBe('lhr')
        ->and($doc['value'][1]['flight']['origin'])->toBe('lhr');
});

it('GET /Passengers with $select=name and $count=true', function () {
    $response = $this->get('/odata/Passengers?$select=name&$count=true');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['@odata.count'])->toBe(3)
        ->and($doc['value'][0])->toBe(['name' => 'Alice']);
});

it('GET /Flights(1)/passengers with $expand=flight returns nested flight on each passenger', function () {
    $response = $this->get('/odata/Flights(1)/passengers?$expand=flight');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(2)
        ->and($doc['value'][0])->toHaveKey('flight')
        ->and($doc['value'][0]['flight']['origin'])->toBe('lhr');
});

// ── Batch with various inner request types ───────────────────────────────────

it('POST /$batch with singleton and property value inner requests', function () {
    $response = $this->postJson('/odata/$batch', [
        'requests' => [
            ['id' => '1', 'method' => 'GET', 'url' => 'DefaultFlight'],
            ['id' => '2', 'method' => 'GET', 'url' => 'Flights(1)/origin'],
            ['id' => '3', 'method' => 'GET', 'url' => 'Passengers?$top=1&$select=name'],
        ],
    ]);

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['responses'])->toHaveCount(3)
        ->and($doc['responses'][0]['status'])->toBe(200)
        ->and($doc['responses'][0]['body']['origin'])->toBe('default')
        ->and($doc['responses'][1]['status'])->toBe(200)
        ->and($doc['responses'][1]['body']['value'])->toBe('lhr')
        ->and($doc['responses'][2]['status'])->toBe(200)
        ->and($doc['responses'][2]['body']['value'])->toHaveCount(1);
});

// ── $search edge cases ───────────────────────────────────────────────────────

it('GET /Flights with $search and $count=true', function () {
    $response = $this->get('/odata/Flights?$search=lhr&$count=true');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['@odata.count'])->toBe(1)
        ->and($doc['value'])->toHaveCount(1);
});

it('GET /Flights with $search and $select=origin', function () {
    $response = $this->get('/odata/Flights?$search=sfo&$select=origin');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(1)
        ->and($doc['value'][0])->toBe(['origin' => 'sfo']);
});

// ── Multiple entity set coverage ─────────────────────────────────────────────

it('GET /Passengers returns all passengers', function () {
    $response = $this->get('/odata/Passengers');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(3)
        ->and($doc['@odata.context'])->toBe('http://localhost/odata/$metadata#Passengers');
});

it('GET /Passengers with $top=2&$skip=1 returns paginated results', function () {
    $response = $this->get('/odata/Passengers?$top=2&$skip=1');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(2)
        ->and($doc['value'][0]['name'])->toBe('Bob');
});

// ── URL encoding ─────────────────────────────────────────────────────────────

it('GET /Flights with URL-encoded $filter', function () {
    // origin eq 'lhr' with URL encoding
    $response = $this->get('/odata/Flights?%24filter=origin%20eq%20%27lhr%27');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(1);
});

// ── Multiple $orderby columns ────────────────────────────────────────────────

it('GET /Passengers with $orderby on two columns', function () {
    $response = $this->get('/odata/Passengers?$orderby=flight_id asc,name desc');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    // flight_id 1: Bob, Alice (name desc); flight_id 2: Carol
    expect($doc['value'][0]['name'])->toBe('Bob')
        ->and($doc['value'][1]['name'])->toBe('Alice')
        ->and($doc['value'][2]['name'])->toBe('Carol');
});

// ── $select with multiple properties ─────────────────────────────────────────

it('GET /Flights with $select=origin,id returns both properties', function () {
    $response = $this->get('/odata/Flights?$select=origin,id');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'][0])->toHaveKeys(['origin', 'id'])
        ->and($doc['value'][0])->not->toHaveKey('destination');
});

// ── Entity key variations ────────────────────────────────────────────────────

it('GET /Flights(id=1) with named key syntax', function () {
    $response = $this->get('/odata/Flights(id=1)');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['id'])->toBe(1)
        ->and($doc['origin'])->toBe('lhr');
});

// ── Batch with $expand inner request ─────────────────────────────────────────

it('POST /$batch with expand inner request', function () {
    $response = $this->postJson('/odata/$batch', [
        'requests' => [
            ['id' => '1', 'method' => 'GET', 'url' => 'Flights(1)?$expand=passengers'],
        ],
    ]);

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['responses'][0]['status'])->toBe(200)
        ->and($doc['responses'][0]['body']['passengers'])->toHaveCount(2);
});

// ── $compute edge cases ──────────────────────────────────────────────────────

it('GET /Flights with $compute using tolower()', function () {
    $response = $this->get("/odata/Flights?\$compute=toupper(origin) as upperOrigin");

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'][0]['upperOrigin'])->toBe('LHR');
});

it('GET /Flights with invalid $compute (missing as) returns 400', function () {
    $response = $this->get('/odata/Flights?$compute=origin');

    $response->assertStatus(400);
});

// ── Service document completeness ────────────────────────────────────────────

it('service document entity sets have url equal to name', function () {
    $response = $this->get('/odata/');
    $doc = json_decode($response->streamedContent(), true);

    foreach ($doc['value'] as $entry) {
        if ($entry['kind'] === 'EntitySet') {
            expect($entry['url'])->toBe($entry['name']);
        }
    }
});

// ── Error response format compliance ─────────────────────────────────────────

it('error responses contain code and message', function () {
    $response = $this->get('/odata/NonExistent');

    $response->assertStatus(400);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc)->toHaveKey('error')
        ->and($doc['error'])->toHaveKey('code')
        ->and($doc['error'])->toHaveKey('message')
        ->and($doc['error']['code'])->toBeString()
        ->and($doc['error']['message'])->toBeString();
});

// ── Server-driven paging (Prefer: odata.maxpagesize) ─────────────────────────

it('Prefer: odata.maxpagesize=2 limits results and returns nextLink', function () {
    $response = $this->get('/odata/Flights', ['Prefer' => 'odata.maxpagesize=2']);

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(2)
        ->and($doc)->toHaveKey('@odata.nextLink')
        ->and($doc['@odata.nextLink'])->toContain('$skip=2');
});

it('Prefer: maxpagesize=2 also works (without odata. prefix)', function () {
    $response = $this->get('/odata/Flights', ['Prefer' => 'maxpagesize=2']);

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(2)
        ->and($doc)->toHaveKey('@odata.nextLink');
});

it('Prefer: odata.maxpagesize=10 returns all rows without nextLink', function () {
    $response = $this->get('/odata/Flights', ['Prefer' => 'odata.maxpagesize=10']);

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(3)
        ->and($doc)->not->toHaveKey('@odata.nextLink');
});

it('Prefer: odata.maxpagesize=1 returns Preference-Applied header', function () {
    $response = $this->get('/odata/Flights', ['Prefer' => 'odata.maxpagesize=1']);

    $response->assertStatus(200);
    expect($response->headers->get('Preference-Applied'))->toBe('odata.maxpagesize=1');

    $doc = json_decode($response->streamedContent(), true);
    expect($doc['value'])->toHaveCount(1);
});

it('$top takes precedence over Prefer maxpagesize', function () {
    $response = $this->get('/odata/Flights?$top=1', ['Prefer' => 'odata.maxpagesize=10']);

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    // $top=1 overrides maxpagesize — no nextLink since client controls paging
    expect($doc['value'])->toHaveCount(1)
        ->and($doc)->not->toHaveKey('@odata.nextLink');
});

it('Prefer: odata.maxpagesize with $count=true returns full count', function () {
    $response = $this->get('/odata/Flights?$count=true', ['Prefer' => 'odata.maxpagesize=1']);

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['@odata.count'])->toBe(3)
        ->and($doc['value'])->toHaveCount(1)
        ->and($doc)->toHaveKey('@odata.nextLink');
});

it('404 error response has correct structure', function () {
    $response = $this->get('/odata/Flights(999)');

    $response->assertStatus(404);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['error']['code'])->toBe('entity_not_found')
        ->and($doc['error']['message'])->toContain('Flights');
});

// ── URL special characters in keys ───────────────────────────────────────────

it('GET /Passengers(id=1) with named key works', function () {
    $response = $this->get('/odata/Passengers(id=1)');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['name'])->toBe('Alice');
});

it('GET /Passengers with $filter using single-quoted string with spaces', function () {
    // This tests that the filter parser handles strings correctly
    $response = $this->get("/odata/Passengers?\$filter=name eq 'Alice'");

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(1);
});

// ── OData-Version header handling ────────────────────────────────────────────

it('response includes OData-Version: 4.0 header on all endpoints', function () {
    $endpoints = ['/odata/', '/odata/Flights', '/odata/Flights(1)', '/odata/$metadata'];

    foreach ($endpoints as $url) {
        $response = $this->get($url);
        expect($response->headers->get('OData-Version'))->toBe('4.0',
            "Missing OData-Version header on {$url}");
    }
});

// ── $filter with various literal types ───────────────────────────────────────

it('GET /Flights with $filter on boolean-like comparison', function () {
    $response = $this->get('/odata/Flights?$filter=id eq 1 or id eq 3');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(2);
});

it('GET /Passengers with $filter using string equality', function () {
    $response = $this->get("/odata/Passengers?\$filter=name eq 'Carol'");

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(1)
        ->and($doc['value'][0]['flight_id'])->toBe(2);
});

// ── Comprehensive combined queries ───────────────────────────────────────────

it('complex query: $filter + $select + $orderby + $top + $skip + $count + $expand', function () {
    $response = $this->get('/odata/Flights?$filter=destination eq \'lax\'&$select=origin&$orderby=origin asc&$top=1&$skip=0&$count=true&$expand=passengers');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['@odata.count'])->toBe(2)
        ->and($doc['value'])->toHaveCount(1)
        ->and($doc['value'][0])->toHaveKeys(['origin', 'passengers'])
        ->and($doc['value'][0])->not->toHaveKey('destination')
        ->and($doc['value'][0]['origin'])->toBe('lhr');
});

it('complex query on Passengers: $filter + $expand + $select + $orderby', function () {
    $response = $this->get('/odata/Passengers?$filter=flight_id eq 1&$expand=flight&$select=name&$orderby=name asc');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(2)
        ->and($doc['value'][0]['name'])->toBe('Alice')
        ->and($doc['value'][0])->toHaveKey('flight')
        ->and($doc['value'][1]['name'])->toBe('Bob');
});

// ── Idempotency / consistency ────────────────────────────────────────────────

it('repeated identical requests return the same data', function () {
    $url = '/odata/Flights?$filter=origin eq \'lhr\'&$select=origin,id';

    $r1 = json_decode($this->get($url)->streamedContent(), true);
    $r2 = json_decode($this->get($url)->streamedContent(), true);

    expect($r1)->toBe($r2);
});

// ── Navigation from Passengers ───────────────────────────────────────────────

it('GET /Passengers(1)/flight returns the parent flight via navigation', function () {
    // This is a single-valued navigation path (belongsTo)
    // The planner should detect 'flight' as a navigation property, not structural
    $response = $this->get('/odata/Passengers(1)/flight');

    // For single-valued navigation, this should resolve to the target entity
    $response->assertStatus(200);
});

it('all responses have OData-Version header', function () {
    $response = $this->get('/odata/Flights');
    expect($response->headers->get('OData-Version'))->toBe('4.0');

    $response = $this->get('/odata/Flights(1)');
    expect($response->headers->get('OData-Version'))->toBe('4.0');

    $response = $this->get('/odata/');
    expect($response->headers->get('OData-Version'))->toBe('4.0');
});

it('collection response has correct content type', function () {
    $response = $this->get('/odata/Flights');
    expect($response->headers->get('Content-Type'))->toContain('application/json');
});

// ── Single entity by key ──────────────────────────────────────────────────────

it('GET /Flights(1) returns a single entity', function () {
    $response = $this->get('/odata/Flights(1)');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc)->toHaveKey('@odata.context')
        ->and($doc['@odata.context'])->toContain('$metadata#Flights/$entity')
        ->and($doc['id'])->toBe(1)
        ->and($doc['origin'])->toBe('lhr');
});

it('GET /Flights(999) returns 404', function () {
    $response = $this->get('/odata/Flights(999)');

    $response->assertStatus(404);
});

// ── $select ──────────────────────────────────────────────────────────────────

it('GET /Flights with $select=origin returns only the selected property', function () {
    $response = $this->get('/odata/Flights?$select=origin');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['@odata.context'])->toContain('$metadata#Flights(origin)')
        ->and($doc['value'])->toHaveCount(3)
        ->and($doc['value'][0])->toHaveKey('origin')
        ->and($doc['value'][0])->not->toHaveKey('destination')
        ->and($doc['value'][0])->not->toHaveKey('id');
});

it('GET /Flights with $select=origin,destination returns both properties', function () {
    $response = $this->get('/odata/Flights?$select=origin,destination');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['@odata.context'])->toContain('$metadata#Flights(origin,destination)')
        ->and($doc['value'][0])->toHaveKeys(['origin', 'destination'])
        ->and($doc['value'][0])->not->toHaveKey('id');
});

it('GET /Flights with $select and $filter combined', function () {
    $response = $this->get('/odata/Flights?$select=origin&$filter=origin eq \'lhr\'');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(1)
        ->and($doc['value'][0])->toBe(['origin' => 'lhr']);
});

it('GET /Flights(1) with $select=origin returns only the selected property', function () {
    $response = $this->get('/odata/Flights(1)?$select=origin');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['@odata.context'])->toContain('$metadata#Flights(origin)/$entity')
        ->and($doc)->toHaveKey('origin')
        ->and($doc)->not->toHaveKey('destination')
        ->and($doc)->not->toHaveKey('id');
});

// ── $count ───────────────────────────────────────────────────────────────────

it('GET /Flights with $count=true includes @odata.count', function () {
    $response = $this->get('/odata/Flights?$count=true');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc)->toHaveKey('@odata.count')
        ->and($doc['@odata.count'])->toBe(3)
        ->and($doc['value'])->toHaveCount(3);
});

it('GET /Flights with $count=true and $top=1 returns count of all rows', function () {
    $response = $this->get('/odata/Flights?$count=true&$top=1');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['@odata.count'])->toBe(3)
        ->and($doc['value'])->toHaveCount(1);
});

it('GET /Flights with $count=true and $filter returns filtered count', function () {
    $response = $this->get('/odata/Flights?$count=true&$filter=destination eq \'lax\'');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['@odata.count'])->toBe(2)
        ->and($doc['value'])->toHaveCount(2);
});

it('GET /Flights without $count omits @odata.count', function () {
    $response = $this->get('/odata/Flights');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc)->not->toHaveKey('@odata.count');
});

// ── Error responses ──────────────────────────────────────────────────────────

it('GET /UnknownSet returns 400 with OData error JSON', function () {
    $response = $this->get('/odata/UnknownSet');

    $response->assertStatus(400);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc)->toHaveKey('error')
        ->and($doc['error'])->toHaveKey('code')
        ->and($doc['error'])->toHaveKey('message');
});

it('GET /Flights with invalid $filter returns 400', function () {
    $response = $this->get('/odata/Flights?$filter=nonexistent eq \'x\'');

    $response->assertStatus(400);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc)->toHaveKey('error')
        ->and($doc['error']['code'])->not->toBeEmpty();
});

it('GET /Flights(999) returns 404 with OData error JSON', function () {
    $response = $this->get('/odata/Flights(999)');

    $response->assertStatus(404);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc)->toHaveKey('error')
        ->and($doc['error']['code'])->toBe('entity_not_found');
});

// ── $batch ───────────────────────────────────────────────────────────────────

it('POST /$batch returns all inner responses', function () {
    $response = $this->postJson('/odata/$batch', [
        'requests' => [
            ['id' => '1', 'method' => 'GET', 'url' => 'Flights'],
            ['id' => '2', 'method' => 'GET', 'url' => 'Flights(1)'],
        ],
    ]);

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc)->toHaveKey('responses')
        ->and($doc['responses'])->toHaveCount(2)
        ->and($doc['responses'][0]['id'])->toBe('1')
        ->and($doc['responses'][0]['status'])->toBe(200)
        ->and($doc['responses'][0]['body']['value'])->toHaveCount(3)
        ->and($doc['responses'][1]['id'])->toBe('2')
        ->and($doc['responses'][1]['status'])->toBe(200)
        ->and($doc['responses'][1]['body']['origin'])->toBe('lhr');
});

it('POST /$batch with query options in inner requests', function () {
    $response = $this->postJson('/odata/$batch', [
        'requests' => [
            ['id' => '1', 'method' => 'GET', 'url' => 'Flights?$top=1'],
            ['id' => '2', 'method' => 'GET', 'url' => 'Flights?$filter=origin eq \'sfo\''],
        ],
    ]);

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['responses'][0]['body']['value'])->toHaveCount(1)
        ->and($doc['responses'][1]['body']['value'])->toHaveCount(1)
        ->and($doc['responses'][1]['body']['value'][0]['origin'])->toBe('sfo');
});

it('POST /$batch with a failing inner request returns partial success', function () {
    $response = $this->postJson('/odata/$batch', [
        'requests' => [
            ['id' => '1', 'method' => 'GET', 'url' => 'Flights(1)'],
            ['id' => '2', 'method' => 'GET', 'url' => 'NonExistent'],
            ['id' => '3', 'method' => 'GET', 'url' => 'Flights(2)'],
        ],
    ]);

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['responses'])->toHaveCount(3)
        ->and($doc['responses'][0]['status'])->toBe(200)
        ->and($doc['responses'][1]['status'])->toBe(400)
        ->and($doc['responses'][1]['body'])->toHaveKey('error')
        ->and($doc['responses'][2]['status'])->toBe(200);
});

it('POST /$batch with missing requests property returns 400', function () {
    $response = $this->postJson('/odata/$batch', ['foo' => 'bar']);

    $response->assertStatus(400);
});

it('POST /$batch rejects non-GET methods', function () {
    $response = $this->postJson('/odata/$batch', [
        'requests' => [
            ['id' => '1', 'method' => 'DELETE', 'url' => 'Flights(1)'],
        ],
    ]);

    $response->assertStatus(400);
});

// ── Function imports ─────────────────────────────────────────────────────────

it('GET /GetFlightCount() returns scalar value', function () {
    $response = $this->get('/odata/GetFlightCount()');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc)->toHaveKey('@odata.context')
        ->and($doc['@odata.context'])->toContain('$metadata#GetFlightCount')
        ->and($doc['value'])->toBe(3);
});

it('GET /GetFlightsByOrigin(origin=\'lhr\') returns filtered count', function () {
    $response = $this->get('/odata/GetFlightsByOrigin(origin=\'lhr\')');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toBe(1);
});

it('GET /GetFlightsByOrigin(origin=\'lax\') returns zero for non-origin', function () {
    $response = $this->get('/odata/GetFlightsByOrigin(origin=\'lax\')');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toBe(0);
});

it('GET /GetFlightsByOrigin(unknown=\'x\') returns 400 for unknown parameter', function () {
    $response = $this->get('/odata/GetFlightsByOrigin(unknown=\'x\')');

    $response->assertStatus(400);
});

it('GET /NonExistentFunction() returns 400', function () {
    $response = $this->get('/odata/NonExistentFunction()');

    $response->assertStatus(400);
});

// ── $expand ──────────────────────────────────────────────────────────────────

it('GET /Flights with $expand=passengers returns nested passengers', function () {
    $response = $this->get('/odata/Flights?$expand=passengers');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    // Flight 1 has 2 passengers, flight 2 has 1, flight 3 has 0
    expect($doc['value'])->toHaveCount(3)
        ->and($doc['value'][0])->toHaveKey('passengers')
        ->and($doc['value'][0]['passengers'])->toHaveCount(2)
        ->and($doc['value'][0]['passengers'][0]['name'])->toBe('Alice')
        ->and($doc['value'][1]['passengers'])->toHaveCount(1)
        ->and($doc['value'][2]['passengers'])->toHaveCount(0);
});

it('GET /Flights(1) with $expand=passengers returns nested passengers on single entity', function () {
    $response = $this->get('/odata/Flights(1)?$expand=passengers');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc)->toHaveKey('passengers')
        ->and($doc['passengers'])->toHaveCount(2)
        ->and($doc['passengers'][0]['name'])->toBe('Alice')
        ->and($doc['passengers'][1]['name'])->toBe('Bob');
});

it('GET /Flights(3) with $expand=passengers returns empty array when no related entities', function () {
    $response = $this->get('/odata/Flights(3)?$expand=passengers');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['passengers'])->toBe([]);
});

it('GET /Flights with $expand=nonexistent returns 400', function () {
    $response = $this->get('/odata/Flights?$expand=nonexistent');

    $response->assertStatus(400);
});

it('GET /Flights with $expand=passengers and $select=origin returns both', function () {
    $response = $this->get('/odata/Flights?$expand=passengers&$select=origin');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'][0])->toHaveKeys(['origin', 'passengers'])
        ->and($doc['value'][0])->not->toHaveKey('destination')
        ->and($doc['value'][0]['passengers'])->toHaveCount(2);
});

// ── Navigation path segments ─────────────────────────────────────────────────

it('GET /Flights(1)/passengers returns passengers for flight 1', function () {
    $response = $this->get('/odata/Flights(1)/passengers');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(2)
        ->and($doc['value'][0]['name'])->toBe('Alice')
        ->and($doc['value'][1]['name'])->toBe('Bob');
});

it('GET /Flights(2)/passengers returns passengers for flight 2', function () {
    $response = $this->get('/odata/Flights(2)/passengers');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(1)
        ->and($doc['value'][0]['name'])->toBe('Carol');
});

it('GET /Flights(3)/passengers returns empty collection for flight with no passengers', function () {
    $response = $this->get('/odata/Flights(3)/passengers');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(0);
});

it('GET /Flights(1)/nonexistent returns 400 for unknown nav property', function () {
    $response = $this->get('/odata/Flights(1)/nonexistent');

    $response->assertStatus(400);
});

it('GET /Flights(1)/passengers with $top=1 returns one passenger', function () {
    $response = $this->get('/odata/Flights(1)/passengers?$top=1');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(1);
});

// ── Nested $expand options ───────────────────────────────────────────────────

it('GET /Flights with $expand=passengers($select=name) returns selected properties', function () {
    $response = $this->get('/odata/Flights?$expand=passengers($select=name)');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    $passenger = $doc['value'][0]['passengers'][0];
    expect($passenger)->toHaveKey('name')
        ->and($passenger['name'])->toBe('Alice');
});

it('GET /Flights with $expand=passengers($top=1) limits nested results', function () {
    $response = $this->get('/odata/Flights?$expand=passengers($top=1)');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    // Flight 1 has 2 passengers but $top=1 limits to 1
    expect($doc['value'][0]['passengers'])->toHaveCount(1);
});

it('GET /Flights with $expand=passengers($filter=name eq \'Alice\') filters nested', function () {
    $response = $this->get('/odata/Flights?$expand=passengers($filter=name eq \'Alice\')');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'][0]['passengers'])->toHaveCount(1)
        ->and($doc['value'][0]['passengers'][0]['name'])->toBe('Alice');
});

it('GET /Flights with $expand=passengers($orderby=name desc) orders nested', function () {
    $response = $this->get('/odata/Flights?$expand=passengers($orderby=name desc)');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    // Flight 1 passengers: Alice, Bob → desc: Bob, Alice
    expect($doc['value'][0]['passengers'][0]['name'])->toBe('Bob')
        ->and($doc['value'][0]['passengers'][1]['name'])->toBe('Alice');
});

// ── Nested $expand within $expand ─────────────────────────────────────────────

it('GET /Passengers with $expand=flight($expand=passengers) returns nested expand', function () {
    $response = $this->get('/odata/Passengers(1)?$expand=flight($expand=passengers)');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc)->toHaveKey('flight')
        ->and($doc['flight'])->toHaveKey('passengers')
        ->and($doc['flight']['passengers'])->toHaveCount(2)
        ->and($doc['flight']['passengers'][0]['name'])->toBe('Alice');
});

it('GET /Flights with $expand=passengers($expand=flight) returns deeply nested', function () {
    $response = $this->get('/odata/Flights(1)?$expand=passengers($expand=flight)');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['passengers'])->toHaveCount(2)
        ->and($doc['passengers'][0])->toHaveKey('flight')
        ->and($doc['passengers'][0]['flight']['origin'])->toBe('lhr');
});

// ── Virtual expand (VirtualExpandResolverInterface) ──────────────────────────

it('GET /Flights(1) with $expand=stats returns virtual navigation data', function () {
    $response = $this->get('/odata/Flights(1)?$expand=stats');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc)->toHaveKey('stats')
        ->and($doc['stats'])->toHaveCount(1)
        ->and($doc['stats'][0]['metric'])->toBe('passenger_count')
        ->and($doc['stats'][0]['value'])->toBe(2);
});

it('GET /Flights(3) with $expand=stats returns zero-count for flight with no passengers', function () {
    $response = $this->get('/odata/Flights(3)?$expand=stats');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['stats'])->toHaveCount(1)
        ->and($doc['stats'][0]['value'])->toBe(0);
});

it('GET /Flights with $expand=stats returns stats on each row in collection', function () {
    $response = $this->get('/odata/Flights?$expand=stats');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(3)
        ->and($doc['value'][0])->toHaveKey('stats')
        ->and($doc['value'][0]['stats'][0]['value'])->toBe(2)
        ->and($doc['value'][1]['stats'][0]['value'])->toBe(1)
        ->and($doc['value'][2]['stats'][0]['value'])->toBe(0);
});

it('GET /Flights(1) with $expand=passengers,stats returns both real and virtual expands', function () {
    $response = $this->get('/odata/Flights(1)?$expand=passengers,stats');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc)->toHaveKey('passengers')
        ->and($doc['passengers'])->toHaveCount(2)
        ->and($doc)->toHaveKey('stats')
        ->and($doc['stats'][0]['metric'])->toBe('passenger_count');
});

it('$metadata includes FlightStats entity type and navigation binding', function () {
    $response = $this->get('/odata/$metadata');
    $xml = $response->streamedContent();

    expect($xml)->toContain('FlightStat')
        ->and($xml)->toContain('FlightStats')
        ->and($xml)->toContain('NavigationProperty Name="stats"');
});

// ── Singletons ───────────────────────────────────────────────────────────────

it('GET /DefaultFlight returns the singleton entity', function () {
    $response = $this->get('/odata/DefaultFlight');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc)->toHaveKey('@odata.context')
        ->and($doc['@odata.context'])->toContain('$metadata#DefaultFlight')
        ->and($doc['origin'])->toBe('default')
        ->and($doc['destination'])->toBe('default');
});

it('GET /DefaultFlight with $select=origin returns only selected property', function () {
    $response = $this->get('/odata/DefaultFlight?$select=origin');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc)->toHaveKey('origin')
        ->and($doc)->not->toHaveKey('destination')
        ->and($doc)->not->toHaveKey('id');
});

// ── Single-valued $expand ────────────────────────────────────────────────────

it('GET /Passengers(1) with $expand=flight returns the parent flight', function () {
    $response = $this->get('/odata/Passengers(1)?$expand=flight');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc)->toHaveKey('flight')
        ->and($doc['flight'])->toBeArray()
        ->and($doc['flight']['origin'])->toBe('lhr');
});

it('GET /Passengers with $expand=flight returns flight on each passenger', function () {
    $response = $this->get('/odata/Passengers?$expand=flight');

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['value'])->toHaveCount(3)
        ->and($doc['value'][0]['flight']['origin'])->toBe('lhr')
        ->and($doc['value'][2]['flight']['origin'])->toBe('sfo');
});

// ── CSRF token handshake ─────────────────────────────────────────────────────

it('HEAD / returns 200 with X-CSRF-Token header', function () {
    $this->app['config']->set('session.driver', 'array');
    $this->app['session']->regenerateToken();

    $response = $this->call('HEAD', '/odata/');

    $response->assertStatus(200);
    expect($response->headers->get('X-CSRF-Token'))->toBe(csrf_token());
});

it('HEAD /Flights is rejected as method not allowed', function () {
    $response = $this->call('HEAD', '/odata/Flights');

    $response->assertStatus(400);
});

// ── BelongsToMany navigation (anchor path) ──────────────────────────────────

it('GET /Flights(1)/airports returns airports via BelongsToMany', function () {
    $response = $this->get('/odata/Flights(1)/airports');

    $response->assertStatus(200);
    $data = json_decode($response->streamedContent(), true);
    expect($data['value'])->toHaveCount(2)
        ->and(array_column($data['value'], 'code'))->toContain('LHR')
        ->and(array_column($data['value'], 'code'))->toContain('LAX');
});

it('GET /Flights(1)/airports returns correct IDs (not pivot table IDs)', function () {
    $response = $this->get('/odata/Flights(1)/airports');

    $data = json_decode($response->streamedContent(), true);
    $airportIds = array_column($data['value'], 'id');
    // Airport IDs should be 1 (Heathrow) and 2 (LAX), NOT pivot IDs (1, 2).
    // In this case they happen to match, so verify by name too.
    $byId = [];
    foreach ($data['value'] as $row) {
        $byId[$row['id']] = $row['name'];
    }
    expect($byId[1])->toBe('Heathrow')
        ->and($byId[2])->toBe('Los Angeles Intl');
});

it('GET /Airports(2)/flights returns flights via BelongsToMany (reverse)', function () {
    $response = $this->get('/odata/Airports(2)/flights');

    $response->assertStatus(200);
    $data = json_decode($response->streamedContent(), true);
    // LAX (id=2) is destination for flights 1 and 2
    expect($data['value'])->toHaveCount(2);
});

it('GET /Flights(3)/airports returns empty collection when no pivot rows', function () {
    $response = $this->get('/odata/Flights(3)/airports');

    $response->assertStatus(200);
    $data = json_decode($response->streamedContent(), true);
    expect($data['value'])->toHaveCount(0);
});

it('GET /Flights(1)/airports with $top=1 applies pagination', function () {
    $response = $this->get('/odata/Flights(1)/airports?$top=1');

    $response->assertStatus(200);
    $data = json_decode($response->streamedContent(), true);
    expect($data['value'])->toHaveCount(1);
});

it('GET /Flights(1)/airports with $filter filters BelongsToMany results', function () {
    $response = $this->get('/odata/Flights(1)/airports?$filter=code eq \'LHR\'');

    $response->assertStatus(200);
    $data = json_decode($response->streamedContent(), true);
    expect($data['value'])->toHaveCount(1)
        ->and($data['value'][0]['code'])->toBe('LHR');
});

it('GET /Flights(1)/airports with $select returns only selected properties', function () {
    $response = $this->get('/odata/Flights(1)/airports?$select=name');

    $response->assertStatus(200);
    $data = json_decode($response->streamedContent(), true);
    expect($data['value'])->toHaveCount(2)
        ->and(array_keys($data['value'][0]))->toBe(['name']);
});

it('GET /Flights(1)/airports with $orderby sorts BelongsToMany results', function () {
    $response = $this->get('/odata/Flights(1)/airports?$orderby=code desc');

    $response->assertStatus(200);
    $data = json_decode($response->streamedContent(), true);
    expect($data['value'][0]['code'])->toBe('LHR')
        ->and($data['value'][1]['code'])->toBe('LAX');
});

it('GET /Flights(1)/airports with $count=true returns count', function () {
    $response = $this->get('/odata/Flights(1)/airports?$count=true');

    $response->assertStatus(200);
    $data = json_decode($response->streamedContent(), true);
    expect($data['@odata.count'])->toBe(2);
});

// ── BelongsToMany $expand (eager loading path) ──────────────────────────────

it('GET /Flights(1) with $expand=airports returns nested airports', function () {
    $response = $this->get('/odata/Flights(1)?$expand=airports');

    $response->assertStatus(200);
    $data = json_decode($response->streamedContent(), true);
    expect($data)->toHaveKey('airports')
        ->and($data['airports'])->toHaveCount(2)
        ->and(array_column($data['airports'], 'code'))->toContain('LHR')
        ->and(array_column($data['airports'], 'code'))->toContain('LAX');
});

it('GET /Flights(1) with $expand=airports returns correct IDs (not pivot IDs)', function () {
    $response = $this->get('/odata/Flights(1)?$expand=airports');

    $data = json_decode($response->streamedContent(), true);
    $byId = [];
    foreach ($data['airports'] as $row) {
        $byId[$row['id']] = $row['name'];
    }
    expect($byId[1])->toBe('Heathrow')
        ->and($byId[2])->toBe('Los Angeles Intl');
});

it('GET /Flights with $expand=airports returns airports on each flight', function () {
    $response = $this->get('/odata/Flights?$expand=airports');

    $response->assertStatus(200);
    $data = json_decode($response->streamedContent(), true);
    // Flight 1: 2 airports, Flight 2: 2 airports, Flight 3: 0 airports
    expect($data['value'][0]['airports'])->toHaveCount(2)
        ->and($data['value'][1]['airports'])->toHaveCount(2)
        ->and($data['value'][2]['airports'])->toHaveCount(0);
});

it('GET /Flights(3) with $expand=airports returns empty array when no pivot rows', function () {
    $response = $this->get('/odata/Flights(3)?$expand=airports');

    $response->assertStatus(200);
    $data = json_decode($response->streamedContent(), true);
    expect($data['airports'])->toBe([]);
});

it('GET /Flights with $expand=passengers,airports returns both HasMany and BelongsToMany', function () {
    $response = $this->get('/odata/Flights(1)?$expand=passengers,airports');

    $response->assertStatus(200);
    $data = json_decode($response->streamedContent(), true);
    expect($data['passengers'])->toHaveCount(2)
        ->and($data['airports'])->toHaveCount(2);
});

it('GET /Flights with $expand=airports($select=name) returns selected properties', function () {
    $response = $this->get('/odata/Flights(1)?$expand=airports($select=name)');

    $response->assertStatus(200);
    $data = json_decode($response->streamedContent(), true);
    expect($data['airports'])->toHaveCount(2);
    // The PK (id) is always included for relation matching.
    // BelongsToMany also includes 'pivot' from Eloquent's toArray().
    expect($data['airports'][0])->toHaveKey('name')
        ->and($data['airports'][0])->toHaveKey('id')
        ->and($data['airports'][0])->not->toHaveKey('code');
});

it('GET /Flights with $expand=airports($filter=code eq \'LHR\') filters nested BelongsToMany', function () {
    $response = $this->get('/odata/Flights(1)?$expand=airports($filter=code eq \'LHR\')');

    $response->assertStatus(200);
    $data = json_decode($response->streamedContent(), true);
    expect($data['airports'])->toHaveCount(1)
        ->and($data['airports'][0]['code'])->toBe('LHR');
});

it('GET /Airports(1) with $expand=flights returns flights via reverse BelongsToMany', function () {
    $response = $this->get('/odata/Airports(1)?$expand=flights');

    $response->assertStatus(200);
    $data = json_decode($response->streamedContent(), true);
    // Heathrow (id=1) is origin for flight 1 only
    expect($data['flights'])->toHaveCount(1)
        ->and($data['flights'][0]['origin'])->toBe('lhr');
});
