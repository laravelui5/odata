<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use LaravelUi5\OData\Fixtures\FlightServiceRegistry;
use LaravelUi5\OData\Fixtures\Models\Airport;
use LaravelUi5\OData\Fixtures\Models\Flight;
use LaravelUi5\OData\Fixtures\Models\Passenger;
use LaravelUi5\OData\Protocol\Planning\EntityQueryPlan;
use LaravelUi5\OData\Protocol\Planning\EntitySetQueryPlan;
use LaravelUi5\OData\Protocol\Planning\ExpandList;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Service\Contracts\QueryPlanInterface;
use LaravelUi5\OData\Service\Contracts\ReadAuthorizerInterface;
use LaravelUi5\OData\Service\ReadContext;
use LaravelUi5\OData\Service\ReadMessage;
use LaravelUi5\OData\Tests\TestCase;

/**
 * Slice O2 — the honest-partial model: a gated `$expand` is pruned from the response (200)
 * and reported in a `sap-messages` header, rather than failing the whole read.
 *
 * `expandDenier()` builds an enforcer that walks the plan's expand tree and drops every expand
 * pointing at the named set — the shape an SDK `#[Read]` enforcer will take.
 */
uses(TestCase::class)
    ->beforeEach(function () {
        $this->withExceptionHandling();

        $this->app->instance(ODataServiceRegistryInterface::class, new FlightServiceRegistry());

        Flight::insert([
            ['origin' => 'lhr', 'destination' => 'lax', 'gate' => 1, 'duration' => 41100.0],
            ['origin' => 'sfo', 'destination' => 'lax', 'gate' => 2, 'duration' => 2133.0],
        ]);

        Passenger::insert([
            ['name' => 'Alice', 'flight_id' => 1],
            ['name' => 'Bob',   'flight_id' => 1],
            ['name' => 'Carol', 'flight_id' => 2],
        ]);

        Airport::insert([
            ['name' => 'Heathrow', 'code' => 'LHR'],
            ['name' => 'Los Angeles Intl', 'code' => 'LAX'],
        ]);
    });

/** An enforcer that drops every $expand targeting $set, at any depth. */
function expandDenier(string $set): ReadAuthorizerInterface
{
    return new class ($set) implements ReadAuthorizerInterface {
        public function __construct(private string $set) {}

        public function authorize(QueryPlanInterface $plan, Request $request, ReadContext $read): void
        {
            if ($plan instanceof EntitySetQueryPlan || $plan instanceof EntityQueryPlan) {
                $this->walk($plan->expand, $read);
            }
        }

        private function walk(ExpandList $list, ReadContext $read): void
        {
            foreach ($list->items as $item) {
                if ($item->targetSet->getName() === $this->set) {
                    $read->denyDrop($this->set, new ReadMessage(
                        code: 'read_forbidden',
                        message: "You may not read {$this->set}.",
                        numericSeverity: 3,
                        target: $item->property->getName(),
                    ));
                }

                $this->walk($item->expand, $read);
            }
        }
    };
}

it('serves the expand normally under the allow-all default (no header)', function () {
    $response = $this->get('/odata/Flights?$expand=passengers');

    $response->assertStatus(200);
    expect($response->headers->has('sap-messages'))->toBeFalse();

    $doc = json_decode($response->streamedContent(), true);
    expect($doc['value'][0])->toHaveKey('passengers')
        ->and($doc['value'][0]['passengers'])->toHaveCount(2);
});

it('prunes a gated $expand on a collection and reports it in sap-messages (200)', function () {
    $this->app->bind(ReadAuthorizerInterface::class, fn () => expandDenier('Passengers'));

    $response = $this->get('/odata/Flights?$expand=passengers');

    // The primary read succeeds; only the gated side-fetch is withheld.
    $response->assertStatus(200);

    $doc = json_decode($response->streamedContent(), true);
    expect($doc['value'])->toHaveCount(2)
        ->and($doc['value'][0])->not->toHaveKey('passengers');

    // The drop is announced honestly — empty-because-denied, not empty-because-none.
    expect($response->headers->has('sap-messages'))->toBeTrue();
    $messages = json_decode($response->headers->get('sap-messages'), true);
    expect($messages)->toHaveCount(1)
        ->and($messages[0]['code'])->toBe('read_forbidden')
        ->and($messages[0]['numericSeverity'])->toBe(3)
        ->and($messages[0]['target'])->toBe('passengers');
});

it('prunes a gated $expand on a single entity and reports it in sap-messages (200)', function () {
    $this->app->bind(ReadAuthorizerInterface::class, fn () => expandDenier('Passengers'));

    $response = $this->get('/odata/Flights(1)?$expand=passengers&$select=origin');

    $response->assertStatus(200);

    $doc = json_decode($response->streamedContent(), true);
    expect($doc)->toHaveKey('origin')
        ->and($doc)->not->toHaveKey('passengers');

    expect($response->headers->has('sap-messages'))->toBeTrue();
    $messages = json_decode($response->headers->get('sap-messages'), true);
    expect($messages[0]['target'])->toBe('passengers');
});

it('leaves an ungated $expand untouched', function () {
    $this->app->bind(ReadAuthorizerInterface::class, fn () => expandDenier('Airports')); // deny a DIFFERENT set

    $response = $this->get('/odata/Flights?$expand=passengers');

    $response->assertStatus(200);
    expect($response->headers->has('sap-messages'))->toBeFalse();

    $doc = json_decode($response->streamedContent(), true);
    expect($doc['value'][0])->toHaveKey('passengers');
});
