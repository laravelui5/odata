<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Execution;

use LaravelUi5\OData\Http\ODataResponse;
use LaravelUi5\OData\Exception\NotFoundException;
use LaravelUi5\OData\Exception\NotImplementedException;
use LaravelUi5\OData\Protocol\Planning\PropertyValuePlan;
use LaravelUi5\OData\Service\Contracts\EntityResolverInterface;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaInterface;

/**
 * Handles property value access: /EntitySet(key)/property[/$value]
 *
 * Returns the property value as JSON (wrapped) or raw (when $value suffix).
 */
final readonly class PropertyValueHandler
{
    public function __construct(
        private RuntimeSchemaInterface $schema,
        private string $serviceRoot,
    ) {}

    public function handle(PropertyValuePlan $plan): ODataResponse
    {
        $resolver = $this->schema->getResolver($plan->target);

        if (!($resolver instanceof EntityResolverInterface)) {
            throw new NotImplementedException(
                'entity_resolver_not_supported',
                sprintf('The resolver for "%s" does not support single-entity lookups.', $plan->target->getName())
            );
        }

        // Build a minimal EntityQueryPlan to reuse resolveOne().
        $entityPlan = new \LaravelUi5\OData\Protocol\Planning\EntityQueryPlan(
            target: $plan->target,
            key:    $plan->key,
            select: new \LaravelUi5\OData\Protocol\Planning\SelectList(),
            expand: new \LaravelUi5\OData\Protocol\Planning\ExpandList(),
        );

        $entity = $resolver->resolveOne($entityPlan);

        if ($entity === null) {
            throw new NotFoundException(
                'entity_not_found',
                sprintf('No entity found in "%s" matching the given key.', $plan->target->getName())
            );
        }

        $propName = $plan->property->getName();
        $value    = $entity[$propName] ?? null;

        if ($plan->rawValue) {
            // /$value — return raw value with text/plain
            $response = new ODataResponse(null, 200, [
                'Content-Type' => 'text/plain;charset=utf-8',
                'OData-Version' => '4.0',
            ]);
            $response->setCallback(static function () use ($value): void {
                echo (string) $value;
            });
            return $response;
        }

        // Property wrapped in JSON context
        $context = $this->serviceRoot . '$metadata#' . $plan->target->getName()
                 . '(' . $propName . ')/$entity';

        $response = new ODataResponse(null, 200, [
            'Content-Type' => 'application/json;odata.metadata=minimal;charset=utf-8',
            'OData-Version' => '4.0',
        ]);

        $response->setCallback(static function () use ($context, $value): void {
            echo json_encode(
                ['@odata.context' => $context, 'value' => $value],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
        });

        return $response;
    }
}
