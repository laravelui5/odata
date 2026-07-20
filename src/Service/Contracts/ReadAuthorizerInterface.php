<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Contracts;

use Illuminate\Http\Request;
use LaravelUi5\OData\Service\ReadContext;

/**
 * The read-authorization forward-exit.
 *
 * OData is security-agnostic: it ships this seam and a no-op default
 * ({@see \LaravelUi5\OData\Service\AllowAllReadAuthorizer}). A host that knows about actors
 * and permissions binds its own implementation — via the `odata.read_authorizer` config key
 * or by rebinding this interface — and records verdicts into the {@see ReadContext}:
 *
 *   - `denyHard()` on a primary / root target → the controller answers a 403;
 *   - `denyDrop()` on an `$expand` target → the engine prunes it and emits a `sap-messages`
 *     warning (the honest-partial model);
 *   - no verdict / `allow()` → the read proceeds.
 *
 * The `$plan` is typed as the marker {@see QueryPlanInterface} to respect the
 * Service → Protocol ring boundary; an enforcer downcasts to the concrete plan
 * (`EntitySetQueryPlan`, `EntityQueryPlan`, …) to read its target set. It must not throw for
 * an authorization decision — it records into the collector, and the caller enforces.
 */
interface ReadAuthorizerInterface
{
    public function authorize(QueryPlanInterface $plan, Request $request, ReadContext $read): void;
}
