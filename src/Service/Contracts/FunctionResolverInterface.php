<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Contracts;

/**
 * Resolves a function-invocation query plan into a return value.
 *
 * Implementations live in Driver\ and execute the function call, returning
 * whatever the function declares as its return type. The plan parameter is
 * typed as QueryPlanInterface to keep Service\ free of Protocol\ imports;
 * at runtime the value is always a FunctionInvocationPlan.
 *
 * @see QueryPlanInterface
 */
interface FunctionResolverInterface
{
    /**
     * Execute the function and return its result.
     *
     * @param QueryPlanInterface $plan  At runtime always Protocol\Planning\FunctionInvocationPlan
     */
    public function resolve(QueryPlanInterface $plan): mixed;
}
