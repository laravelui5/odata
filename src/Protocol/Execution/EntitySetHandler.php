<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Execution;

use LaravelUi5\OData\Http\ODataResponse;
use LaravelUi5\OData\Protocol\Planning\EntitySetQueryPlan;
use LaravelUi5\OData\Protocol\Planning\SelectList;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaInterface;

/**
 * Handles EntitySetQueryPlan — produces a streamed OData JSON collection.
 *
 * Emits:
 *   {"@odata.context":"<serviceRoot>$metadata#<SetName>","value":[
 *     {"id":1,"name":"..."},
 *     ...
 *   ]}
 *
 * Rows are streamed directly from the resolver generator — no buffering.
 */
final readonly class EntitySetHandler
{
    public function __construct(
        private RuntimeSchemaInterface $schema,
        private string $serviceRoot,
    ) {}

    public function handle(EntitySetQueryPlan $plan): ODataResponse
    {
        $context    = $this->serviceRoot . '$metadata#' . $plan->target->getName()
                    . SelectHelper::contextFragment($plan->select);
        $resolver   = $this->schema->getResolver($plan->target);
        $selectKeys = SelectHelper::allowedKeys($plan->select, $plan->expand, $plan->compute);

        // Server-driven paging: maxPageSize applies when no explicit $top.
        $pageSize = ($plan->maxPageSize !== null && $plan->top === null)
            ? $plan->maxPageSize
            : null;

        $headers = [
            'Content-Type' => 'application/json;odata.metadata=minimal;charset=utf-8',
            'OData-Version' => '4.0',
        ];

        if ($plan->maxPageSize !== null) {
            $headers['Preference-Applied'] = 'odata.maxpagesize=' . $plan->maxPageSize;
        }

        $response = new ODataResponse(null, 200, $headers);

        $count       = $plan->count ? $resolver->count($plan) : null;
        $serviceRoot = $this->serviceRoot;
        $setName     = $plan->target->getName();

        $response->setCallback(static function () use ($context, $resolver, $plan, $selectKeys, $count, $pageSize, $serviceRoot, $setName): void {
            $generator = $resolver->resolve($plan);

            echo '{"@odata.context":' . json_encode($context);

            if ($count !== null) {
                echo ',"@odata.count":' . $count;
            }

            echo ',"value":[';

            $first    = true;
            $emitted  = 0;
            $hasMore  = false;

            foreach ($generator as $row) {
                if ($pageSize !== null && $emitted >= $pageSize) {
                    $hasMore = true;
                    break;
                }

                if (!$first) {
                    echo ',';
                }
                $row = $selectKeys !== null ? array_intersect_key($row, $selectKeys) : $row;
                echo json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $first = false;
                $emitted++;
            }

            echo ']';

            if ($hasMore) {
                $skip = ($plan->skip ?? 0) + $emitted;
                $nextLink = $serviceRoot . $setName . '?$skip=' . $skip;
                echo ',"@odata.nextLink":' . json_encode($nextLink);
            }

            echo '}';
        });

        return $response;
    }
}
