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
        private WireFormat $format = new WireFormat(),
        private ?RequestTarget $target = null,
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
            'Content-Type' => $this->format->contentType(),
            'OData-Version' => ODataVersion::current(),
        ];

        if ($plan->maxPageSize !== null) {
            $headers['Preference-Applied'] = 'odata.maxpagesize=' . $plan->maxPageSize;
        }

        $response = new ODataResponse(null, 200, $headers);

        $count       = $plan->count ? $resolver->count($plan) : null;
        $serviceRoot = $this->serviceRoot;
        $setName     = $plan->target->getName();
        $coercion    = new RowCoercion($plan->target->getEntityType(), $this->format);
        $target      = $this->target;

        // Run the query up to the first row *before* the response is committed. The generator
        // builds and executes it lazily; started inside the stream callback, a refused $filter
        // (501) or a SQL error would arrive after a 200 had been sent. Rows still stream.
        $generator = $resolver->resolve($plan);
        $generator->current();

        $response->setCallback(static function () use ($context, $generator, $plan, $selectKeys, $count, $pageSize, $serviceRoot, $setName, $coercion, $target): void {

            echo '{"@odata.context":' . json_encode($context);

            if ($count !== null) {
                echo ',"@odata.count":' . $count;
            }

            echo ',"value":[';

            $first    = true;
            $emitted  = 0;
            $hasMore  = false;

            // The generator was started before the response (current()), so continue it
            // rather than foreach, which would try to rewind it.
            for (; $generator->valid(); $generator->next()) {
                if ($pageSize !== null && $emitted >= $pageSize) {
                    $hasMore = true;
                    break;
                }

                if (!$first) {
                    echo ',';
                }
                $row = $generator->current();
                $row = $selectKeys !== null ? array_intersect_key($row, $selectKeys) : $row;
                $row = $coercion->apply($row);
                echo json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $first = false;
                $emitted++;
            }

            echo ']';

            if ($hasMore) {
                $skip = ($plan->skip ?? 0) + $emitted;
                // The same query, only $skip moved. Without the request (a plan executed
                // outside the HTTP layer) the set name is all there is to link to.
                $nextLink = $target !== null
                    ? $target->nextLink($serviceRoot, $skip)
                    : $serviceRoot . $setName . '?$skip=' . $skip;
                echo ',"@odata.nextLink":' . json_encode($nextLink);
            }

            echo '}';
        });

        return $response;
    }
}
