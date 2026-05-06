<?php

declare(strict_types=1);

use LaravelUi5\OData\Fixtures\EnumServiceRegistry;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Tests\TestCase;

/**
 * Tier-4 HTTP round-trip for the PHP-backed-enum → Edm.EnumType bridge.
 *
 * Verifies that a column declared as a backed-enum class-string lands as
 * an EnumType in $metadata and emits the symbolic member name on the wire.
 */
uses(TestCase::class)
    ->beforeEach(function () {
        $this->withExceptionHandling();

        $this->app->instance(
            ODataServiceRegistryInterface::class,
            new EnumServiceRegistry(),
        );

        \LaravelUi5\OData\Fixtures\Models\Passenger::insert([
            ['name' => 'Alice', 'flight_id' => 1, 'colour' => 1],   // Red
            ['name' => 'Bob',   'flight_id' => 1, 'colour' => 2],   // Green
            ['name' => 'Carol', 'flight_id' => 2, 'colour' => 8],   // Brown
            ['name' => 'Dave',  'flight_id' => 2, 'colour' => 99],  // unknown — drift case
        ]);
    });

describe('EnumType $metadata', function () {
    it('emits the EnumType element with all members', function () {
        $xml = $this->get('/odata/$metadata')->streamedContent();

        expect($xml)
            ->toContain('<EnumType Name="Colour" UnderlyingType="Edm.Int32"')
            ->toContain('<Member Name="Red" Value="1"/>')
            ->toContain('<Member Name="Green" Value="2"/>')
            ->toContain('<Member Name="Blue" Value="4"/>')
            ->toContain('<Member Name="Brown" Value="8"/>');
    });

    it('references the EnumType from the entity-set property', function () {
        $xml = $this->get('/odata/$metadata')->streamedContent();

        expect($xml)->toContain('<Property Name="colour" Type="Test.Ns.Colour"/>');
    });
});

describe('EnumType wire format', function () {
    it('emits the symbolic member name in JSON output', function () {
        $data = json_decode(
            $this->get('/odata/PassengerColours?$orderby=id')->streamedContent(),
            true,
        );

        $byName = array_column($data['value'], null, 'name');

        expect($byName['Alice']['colour'])->toBe('Red');
        expect($byName['Bob']['colour'])->toBe('Green');
        expect($byName['Carol']['colour'])->toBe('Brown');
    });

    it('falls through to the raw int when the value is not a known member', function () {
        $data = json_decode(
            $this->get('/odata/PassengerColours?$orderby=id')->streamedContent(),
            true,
        );

        $dave = collect($data['value'])->firstWhere('name', 'Dave');
        expect($dave['colour'])->toBe(99);
    });

    it('coerces a single entity lookup as well as the collection', function () {
        $alice = collect(json_decode(
            $this->get('/odata/PassengerColours?$orderby=id')->streamedContent(),
            true,
        )['value'])->firstWhere('name', 'Alice');

        $entity = json_decode(
            $this->get('/odata/PassengerColours(' . $alice['id'] . ')')->streamedContent(),
            true,
        );

        expect($entity['colour'])->toBe('Red');
    });
});
