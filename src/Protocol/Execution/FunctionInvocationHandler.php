<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Execution;

use LaravelUi5\OData\Http\ODataResponse;
use LaravelUi5\OData\Protocol\Planning\FunctionInvocationPlan;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaInterface;

/**
 * Handles FunctionInvocationPlan — executes a function import and returns
 * the result as JSON.
 *
 * The function resolver is looked up from RuntimeSchema by the
 * FunctionImportInterface object reference on the plan.
 */
final readonly class FunctionInvocationHandler
{
    public function __construct(
        private RuntimeSchemaInterface $schema,
        private string $serviceRoot,
    ) {}

    public function handle(FunctionInvocationPlan $plan): ODataResponse
    {
        $resolver = $this->schema->getFunctionResolver($plan->import);
        $result   = $resolver->resolve($plan);
        $context  = $this->serviceRoot . '$metadata#' . $plan->import->getName();

        $response = new ODataResponse(null, 200, [
            'Content-Type' => 'application/json;odata.metadata=minimal;charset=utf-8',
            'OData-Version' => '4.0',
        ]);

        $response->setCallback(static function () use ($context, $result): void {
            if (is_array($result) || is_object($result)) {
                $payload = array_merge(['@odata.context' => $context], (array) $result);
                echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(
                    ['@odata.context' => $context, 'value' => $result],
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                );
            }
        });

        return $response;
    }
}
