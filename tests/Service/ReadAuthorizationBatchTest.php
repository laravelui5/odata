<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use LaravelUi5\OData\Fixtures\FlightServiceRegistry;
use LaravelUi5\OData\Fixtures\Models\Flight;
use LaravelUi5\OData\Fixtures\Models\Passenger;
use LaravelUi5\OData\Protocol\Planning\EntityQueryPlan;
use LaravelUi5\OData\Protocol\Planning\EntitySetQueryPlan;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Service\Contracts\QueryPlanInterface;
use LaravelUi5\OData\Service\Contracts\ReadAuthorizerInterface;
use LaravelUi5\OData\Service\ReadContext;
use LaravelUi5\OData\Service\ReadMessage;
use LaravelUi5\OData\Tests\TestCase;

/**
 * Slice O3 — $batch inner requests run through the same read-authz gate as direct reads.
 * A denied inner request produces a per-inner 403 entry; sibling requests are unaffected
 * (the batch envelope carries per-request status natively).
 */
uses(TestCase::class)
    ->beforeEach(function () {
        $this->withExceptionHandling();

        $this->app->instance(ODataServiceRegistryInterface::class, new FlightServiceRegistry());

        Flight::insert([
            ['origin' => 'lhr', 'destination' => 'lax', 'gate' => 1, 'duration' => 41100.0],
        ]);

        Passenger::insert([
            ['name' => 'Alice', 'flight_id' => 1],
        ]);
    });

/** An enforcer that hard-denies reads of the named root set. */
function rootDenier(string $set): ReadAuthorizerInterface
{
    return new class ($set) implements ReadAuthorizerInterface {
        public function __construct(private string $set) {}

        public function authorize(QueryPlanInterface $plan, Request $request, ReadContext $read): void
        {
            $target = match (true) {
                $plan instanceof EntitySetQueryPlan, $plan instanceof EntityQueryPlan => $plan->target->getName(),
                default => null,
            };

            if ($target === $this->set) {
                $read->denyHard($this->set, new ReadMessage('read_forbidden', "You may not read {$this->set}.", 4));
            }
        }
    };
}

it('gates a $batch inner request with a per-inner 403, leaving siblings untouched', function () {
    $this->app->bind(ReadAuthorizerInterface::class, fn () => rootDenier('Flights'));

    $response = $this->postJson('/odata/$batch', [
        'requests' => [
            ['id' => '1', 'method' => 'GET', 'url' => 'Flights'],    // denied
            ['id' => '2', 'method' => 'GET', 'url' => 'Passengers'], // allowed
        ],
    ]);

    // The batch envelope itself succeeds; only the gated inner request is 403.
    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['responses'][0]['status'])->toBe(403)
        ->and($doc['responses'][0]['body']['error']['code'])->toBe('read_forbidden')
        ->and($doc['responses'][1]['status'])->toBe(200)
        ->and($doc['responses'][1]['body']['value'])->toHaveCount(1);
});

it('serves every $batch inner request under the allow-all default', function () {
    $response = $this->postJson('/odata/$batch', [
        'requests' => [
            ['id' => '1', 'method' => 'GET', 'url' => 'Flights'],
            ['id' => '2', 'method' => 'GET', 'url' => 'Passengers'],
        ],
    ]);

    $response->assertStatus(200);
    $doc = json_decode($response->streamedContent(), true);

    expect($doc['responses'][0]['status'])->toBe(200)
        ->and($doc['responses'][1]['status'])->toBe(200);
});
