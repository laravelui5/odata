<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use LaravelUi5\OData\Fixtures\BoundMountFlightService;
use LaravelUi5\OData\Fixtures\Models\Flight;
use LaravelUi5\OData\Http\Controller\OData;
use LaravelUi5\OData\Protocol\Planning\EntitySetQueryPlan;
use LaravelUi5\OData\Service\Contracts\QueryPlanInterface;
use LaravelUi5\OData\Service\Contracts\ReadAuthorizerInterface;
use LaravelUi5\OData\Service\ReadContext;
use LaravelUi5\OData\Service\ReadMessage;
use LaravelUi5\OData\Tests\TestCase;

/**
 * Slice O1 — the read-authorization forward-exit + the 403 root-gate.
 *
 * Composes a service-bound route (the documented forService seam) and drives the gate with a
 * fake host enforcer that denies the `Flights` set — exercising the same plan downcast an SDK
 * enforcer will use (`EntitySetQueryPlan` → `->target->getName()`).
 */
uses(TestCase::class)
    ->beforeEach(function () {
        $this->withExceptionHandling();

        Route::any('alt/{path?}', fn (Request $request, ?string $path = null) =>
            app(OData::class)->forService($request, app(BoundMountFlightService::class))
        )->where('path', '.*');

        Flight::insert([
            ['origin' => 'lhr', 'destination' => 'lax', 'gate' => 1, 'duration' => 41100.0],
            ['origin' => 'sfo', 'destination' => 'lax', 'gate' => 2, 'duration' => 2133.0],
        ]);
    });

it('serves normally under the allow-all default authorizer', function () {
    $response = $this->get('/alt/Flights');

    $response->assertStatus(200);
    expect(json_decode($response->streamedContent(), true)['value'])->toHaveCount(2);
});

it('answers a 403 OData error envelope when the read authorizer denies the set', function () {
    $this->app->bind(ReadAuthorizerInterface::class, fn () => new class implements ReadAuthorizerInterface {
        public function authorize(QueryPlanInterface $plan, Request $request, ReadContext $read): void
        {
            if ($plan instanceof EntitySetQueryPlan && $plan->target->getName() === 'Flights') {
                $read->denyHard('Flights', new ReadMessage(
                    code: 'read_forbidden',
                    message: 'You may not read Flights.',
                    numericSeverity: 4,
                ));
            }
        }
    });

    $response = $this->get('/alt/Flights');

    $response->assertStatus(403);

    $error = json_decode($response->streamedContent(), true)['error'];
    expect($error['code'])->toBe('read_forbidden')
        ->and($error['message'])->toBe('You may not read Flights.');
});

it('leaves $metadata ungated — schema is not data', function () {
    // A set-targeting enforcer never fires on a MetadataQueryPlan, so $metadata stays open
    // even while Flights is denied. The gate is on data reads, not the schema document.
    $this->app->bind(ReadAuthorizerInterface::class, fn () => new class implements ReadAuthorizerInterface {
        public function authorize(QueryPlanInterface $plan, Request $request, ReadContext $read): void
        {
            if ($plan instanceof EntitySetQueryPlan) {
                $read->denyHard($plan->target->getName(), new ReadMessage('read_forbidden', 'no', 4));
            }
        }
    });

    $this->get('/alt/$metadata')->assertStatus(200);
    $this->get('/alt/Flights')->assertStatus(403);
});
