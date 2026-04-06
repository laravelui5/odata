<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Execution;

use LaravelUi5\OData\Http\ODataResponse;
use LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface;
use LaravelUi5\OData\Protocol\Planning\SelectList;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaInterface;

/**
 * Handles singleton requests — returns a single named entity instance.
 *
 * Emits:
 *   {"@odata.context":"<serviceRoot>$metadata#<SingletonName>","prop":"value",...}
 */
final readonly class SingletonHandler
{
    public function __construct(
        private RuntimeSchemaInterface $schema,
        private string $serviceRoot,
    ) {}

    public function handle(SingletonInterface $singleton, SelectList $select): ODataResponse
    {
        $resolver = $this->schema->getSingletonResolver($singleton);
        $entity   = $resolver->resolve();
        $context  = $this->serviceRoot . '$metadata#' . $singleton->getName();

        $selectKeys = SelectHelper::allowedKeys($select);

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
