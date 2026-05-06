<?php

declare(strict_types=1);

use LaravelUi5\OData\Driver\Sql\EloquentEntitySetResolver;
use LaravelUi5\OData\Edm\Container\EntitySet as EdmEntitySet;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\EdmxInterface;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Protocol\Execution\Engine;
use LaravelUi5\OData\Protocol\Planning\EntitySetQueryPlan;
use LaravelUi5\OData\Protocol\Planning\ExpandList;
use LaravelUi5\OData\Protocol\Planning\MetadataQueryPlan;
use LaravelUi5\OData\Protocol\Planning\OrderByList;
use LaravelUi5\OData\Protocol\Planning\SelectList;
use LaravelUi5\OData\Protocol\Planning\ServiceDocumentQueryPlan;
use LaravelUi5\OData\Service\Builder\EdmBuilder;
use LaravelUi5\OData\Service\Builder\RuntimeSchemaBuilder;
use LaravelUi5\OData\Service\Contracts\EntitySetResolverInterface;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaInterface;
use LaravelUi5\OData\Fixtures\Models\Flight;
use LaravelUi5\OData\Tests\TestCase;

/**
 * Tier-4 Pest tests for Protocol\Execution\Engine.
 *
 * Boots Orchestra TestCase + SQLite so the EntitySetHandler can run a real
 * resolver. Tests are black-box: they capture the streamed response body and
 * assert on the decoded JSON / raw XML.
 */
uses(TestCase::class)
    ->beforeEach(function () {
        Flight::insert([
            ['origin' => 'lhr', 'destination' => 'lax', 'gate' => 1, 'duration' => 41100.0],
            ['origin' => 'sfo', 'destination' => 'lax', 'gate' => 2, 'duration' => 2133.0],
        ]);
    });

// ── Helpers ────────────────────────────────────────────────────────────────────

const ENGINE_SERVICE_ROOT = 'http://localhost/odata/';

function engineEdmx(): EdmxInterface
{
    $int32  = new PrimitiveType(EdmPrimitiveType::Int32);
    $string = new PrimitiveType(EdmPrimitiveType::String);

    $idProp          = new Property('id', $int32);
    $originProp      = new Property('origin', $string);
    $destinationProp = new Property('destination', $string);

    $flightType = new EntityType(
        namespace: 'Test.Ns',
        name: 'Flight',
        key: [$idProp],
        declaredProperties: [$idProp, $originProp, $destinationProp],
    );

    $flightSet = new EdmEntitySet('Flights', $flightType);

    return (new EdmBuilder)
        ->namespace('Test.Ns')
        ->addEntityType($flightType)
        ->addEntitySet($flightSet)
        ->build();
}

function engineSchema(): RuntimeSchemaInterface
{
    $edmx      = engineEdmx();
    $flightSet = $edmx->getEntityContainer()->getEntitySet('Flights');

    return (new RuntimeSchemaBuilder($edmx))
        ->bindEntitySet($flightSet, new EloquentEntitySetResolver(Flight::class))
        ->build();
}

/** Capture the streamed body of an ODataResponse. */
function captureResponse(\LaravelUi5\OData\Http\ODataResponse $response): string
{
    ob_start();
    $response->sendContent();
    return ob_get_clean();
}

// ── MetadataHandler tests ──────────────────────────────────────────────────────

it('metadata handler returns XML with correct content-type', function () {
    $edmx   = engineEdmx();
    $plan   = new MetadataQueryPlan($edmx);
    $engine = new Engine(engineSchema(), ENGINE_SERVICE_ROOT);

    $response = $engine->execute($plan);

    expect($response->headers->get('Content-Type'))->toContain('application/xml')
        ->and($response->headers->get('OData-Version'))->toBe('4.0')
        ->and($response->getStatusCode())->toBe(200);
});

it('metadata handler body is valid CSDL XML with edmx root', function () {
    $plan = new MetadataQueryPlan(engineEdmx());
    $body = captureResponse((new Engine(engineSchema(), ENGINE_SERVICE_ROOT))->execute($plan));

    expect($body)->toContain('edmx:Edmx')
        ->and($body)->toContain('Test.Ns')
        ->and($body)->toContain('Flight');
});

// ── ServiceDocumentHandler tests ──────────────────────────────────────────────

it('service document handler returns JSON with correct content-type', function () {
    $plan     = new ServiceDocumentQueryPlan(engineEdmx());
    $response = (new Engine(engineSchema(), ENGINE_SERVICE_ROOT))->execute($plan);

    expect($response->headers->get('Content-Type'))->toContain('application/json')
        ->and($response->getStatusCode())->toBe(200);
});

it('service document body contains context and Flights entity set', function () {
    $plan = new ServiceDocumentQueryPlan(engineEdmx());
    $body = captureResponse((new Engine(engineSchema(), ENGINE_SERVICE_ROOT))->execute($plan));
    $doc  = json_decode($body, true);

    expect($doc)->toHaveKey('@odata.context')
        ->and($doc['@odata.context'])->toBe(ENGINE_SERVICE_ROOT . '$metadata')
        ->and($doc)->toHaveKey('value')
        ->and($doc['value'])->toHaveCount(1)
        ->and($doc['value'][0]['name'])->toBe('Flights')
        ->and($doc['value'][0]['kind'])->toBe('EntitySet')
        ->and($doc['value'][0]['url'])->toBe('Flights');
});

// ── EntitySetHandler tests ─────────────────────────────────────────────────────

it('entity set handler returns JSON with correct content-type', function () {
    $schema   = engineSchema();
    $flightSet = $schema->getEdmx()->getEntityContainer()->getEntitySet('Flights');

    $plan = new EntitySetQueryPlan(
        target:    $flightSet,
        filter:    null,
        select:    new SelectList(),
        expand:    new ExpandList(),
        orderBy:   new OrderByList(),
        top:       null,
        skip:      null,
        skipToken: null,
        count:     false,
    );

    $response = (new Engine($schema, ENGINE_SERVICE_ROOT))->execute($plan);

    expect($response->headers->get('Content-Type'))->toContain('application/json')
        ->and($response->getStatusCode())->toBe(200);
});

it('entity set handler streams all rows with context', function () {
    $schema    = engineSchema();
    $flightSet = $schema->getEdmx()->getEntityContainer()->getEntitySet('Flights');

    $plan = new EntitySetQueryPlan(
        target:    $flightSet,
        filter:    null,
        select:    new SelectList(),
        expand:    new ExpandList(),
        orderBy:   new OrderByList(),
        top:       null,
        skip:      null,
        skipToken: null,
        count:     false,
    );

    $body = captureResponse((new Engine($schema, ENGINE_SERVICE_ROOT))->execute($plan));
    $doc  = json_decode($body, true);

    expect($doc)->toHaveKey('@odata.context')
        ->and($doc['@odata.context'])->toBe(ENGINE_SERVICE_ROOT . '$metadata#Flights')
        ->and($doc)->toHaveKey('value')
        ->and($doc['value'])->toHaveCount(2)
        ->and($doc['value'][0]['origin'])->toBe('lhr')
        ->and($doc['value'][1]['origin'])->toBe('sfo');
});

it('entity set handler respects top', function () {
    $schema    = engineSchema();
    $flightSet = $schema->getEdmx()->getEntityContainer()->getEntitySet('Flights');

    $plan = new EntitySetQueryPlan(
        target:    $flightSet,
        filter:    null,
        select:    new SelectList(),
        expand:    new ExpandList(),
        orderBy:   new OrderByList(),
        top:       1,
        skip:      null,
        skipToken: null,
        count:     false,
    );

    $body = captureResponse((new Engine($schema, ENGINE_SERVICE_ROOT))->execute($plan));
    $doc  = json_decode($body, true);

    expect($doc['value'])->toHaveCount(1);
});

it('engine throws BadRequestException for unsupported plan type', function () {
    $schema = engineSchema();

    // BatchQueryPlan has no handler yet
    $plan = new \LaravelUi5\OData\Protocol\Planning\BatchQueryPlan([], false);

    (new Engine($schema, ENGINE_SERVICE_ROOT))->execute($plan);
})->throws(\LaravelUi5\OData\Exception\BadRequestException::class);
