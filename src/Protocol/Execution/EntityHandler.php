<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Execution;

use LaravelUi5\OData\Http\ODataResponse;
use LaravelUi5\OData\Exception\NotFoundException;
use LaravelUi5\OData\Exception\NotImplementedException;
use LaravelUi5\OData\Protocol\Planning\EntityQueryPlan;
use LaravelUi5\OData\Service\Contracts\EntityResolverInterface;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaInterface;

/**
 * Handles EntityQueryPlan — single entity lookup by key.
 *
 * Emits:
 *   {"@odata.context":"<serviceRoot>$metadata#<SetName>/$entity","id":1,"name":"..."}
 *
 * Returns 404 when no entity with the given key exists.
 * Throws NotImplementedException when the resolver does not implement
 * EntityResolverInterface (driver capability check).
 */
final readonly class EntityHandler
{
    public function __construct(
        private RuntimeSchemaInterface $schema,
        private string $serviceRoot,
    ) {}

    public function handle(EntityQueryPlan $plan): ODataResponse
    {
        $resolver = $this->schema->getResolver($plan->target);

        if (!($resolver instanceof EntityResolverInterface)) {
            throw new NotImplementedException(
                'entity_resolver_not_supported',
                sprintf(
                    'The resolver for "%s" does not implement EntityResolverInterface.',
                    $plan->target->getName()
                )
            );
        }

        $context    = $this->serviceRoot . '$metadata#' . $plan->target->getName()
                    . SelectHelper::contextFragment($plan->select) . '/$entity';
        $entity     = $resolver->resolveOne($plan);

        if ($entity === null) {
            throw new NotFoundException(
                'entity_not_found',
                sprintf('No entity found in "%s" matching the given key.', $plan->target->getName())
            );
        }

        $selectKeys = SelectHelper::allowedKeys($plan->select, $plan->expand);

        $response = new ODataResponse(null, 200, [
            'Content-Type' => 'application/json;odata.metadata=minimal;charset=utf-8',
            'OData-Version' => '4.0',
        ]);

        $response->setCallback(static function () use ($context, $entity, $selectKeys): void {
            $entity  = $selectKeys !== null ? array_intersect_key($entity, $selectKeys) : $entity;
            $payload = array_merge(['@odata.context' => $context], $entity);
            echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        });

        return $response;
    }
}
