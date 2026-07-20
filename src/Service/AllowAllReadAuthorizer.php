<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service;

use Illuminate\Http\Request;
use LaravelUi5\OData\Service\Contracts\QueryPlanInterface;
use LaravelUi5\OData\Service\Contracts\ReadAuthorizerInterface;

/**
 * The default read authorizer: records no verdict, so every read proceeds.
 *
 * Keeps unconfigured OData security-agnostic and backward-compatible — an actor who reaches
 * a URL still gets the rows, exactly as before. A host that wants read gating binds its own
 * {@see ReadAuthorizerInterface} (see the `odata.read_authorizer` config key).
 */
final class AllowAllReadAuthorizer implements ReadAuthorizerInterface
{
    public function authorize(QueryPlanInterface $plan, Request $request, ReadContext $read): void
    {
        // Intentionally empty — no denial recorded, the read proceeds.
    }
}
