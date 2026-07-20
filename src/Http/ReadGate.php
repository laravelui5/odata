<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Http;

use Illuminate\Http\Request;
use LaravelUi5\OData\Exception\ForbiddenException;
use LaravelUi5\OData\Protocol\Execution\Engine;
use LaravelUi5\OData\Protocol\Planning\EntityQueryPlan;
use LaravelUi5\OData\Protocol\Planning\EntitySetQueryPlan;
use LaravelUi5\OData\Protocol\Planning\ExpandPruner;
use LaravelUi5\OData\Protocol\Planning\QueryPlan;
use LaravelUi5\OData\Service\Contracts\ReadAuthorizerInterface;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaInterface;
use LaravelUi5\OData\Service\ReadContext;
use LaravelUi5\OData\Service\ReadMessage;

/**
 * The read-authorization gate: authorize a plan, then execute it.
 *
 * Shared by the direct read path ({@see \LaravelUi5\OData\Http\Controller\OData::forService})
 * and each `$batch` inner request, so both enforce identically:
 *
 *   - a hard denial (a primary / root target) → {@see ForbiddenException} (403);
 *   - a gated `$expand` → pruned from the plan + a `sap-messages` warning on the 200;
 *   - otherwise → served as-is.
 *
 * For a `$batch` inner request the **outer** `$batch` Request is passed: the plan is
 * inner-specific and the actor is request-scoped, so per-set gating is correct — an enforcer
 * decides off the plan and the actor, never `Request::path()`.
 */
final readonly class ReadGate
{
    public function __construct(private ReadAuthorizerInterface $authorizer)
    {
    }

    public function execute(
        QueryPlan $plan,
        Request $request,
        RuntimeSchemaInterface $schema,
        string $endpoint,
    ): ODataResponse {
        $read = new ReadContext();
        $this->authorizer->authorize($plan, $request, $read);

        if ($read->hasHardDenial()) {
            throw ForbiddenException::fromContext($read);
        }

        if ($read->dropped() !== []) {
            $plan = $this->pruneDroppedExpands($plan, $read->dropped());
        }

        $response = (new Engine($schema, $endpoint))->execute($plan);

        if ($read->dropMessages() !== []) {
            $response->headers->set('sap-messages', $this->encodeSapMessages($read->dropMessages()));
        }

        return $response;
    }

    /**
     * Rebuild the plan without the gated `$expand` targets. Read authorization is per entity
     * set, so a dropped set name removes every expand pointing at it, at any depth. Plan types
     * that carry no expands are returned unchanged.
     *
     * @param list<string> $droppedSetNames
     */
    private function pruneDroppedExpands(QueryPlan $plan, array $droppedSetNames): QueryPlan
    {
        if ($plan instanceof EntitySetQueryPlan || $plan instanceof EntityQueryPlan) {
            return $plan->withExpand(ExpandPruner::prune($plan->expand, $droppedSetNames));
        }

        return $plan;
    }

    /**
     * Serialize the drop messages as the standard `sap-messages` header value (a JSON array of
     * unbound messages) — the carrier the UI5 v4 model ingests natively.
     *
     * @param list<ReadMessage> $messages
     */
    private function encodeSapMessages(array $messages): string
    {
        return json_encode(
            array_map(static fn (ReadMessage $message) => $message->toArray(), $messages),
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
    }
}
