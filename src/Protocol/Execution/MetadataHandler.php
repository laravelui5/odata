<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Execution;

use LaravelUi5\OData\Http\ODataResponse;
use LaravelUi5\OData\Protocol\Planning\MetadataQueryPlan;
use LaravelUi5\OData\Service\Serialization\CsdlSerializer;

/**
 * Handles MetadataQueryPlan — produces a CSDL XML response.
 *
 * Delegates to CsdlSerializer (already built in Service\Serialization\).
 * No resolver required; the EdmxInterface is carried by the plan.
 */
final readonly class MetadataHandler
{
    public function handle(MetadataQueryPlan $plan): ODataResponse
    {
        $xml = (new CsdlSerializer)->serialize($plan->edmx);

        $response = new ODataResponse(null, 200, [
            'Content-Type' => 'application/xml;charset=utf-8',
            'OData-Version' => '4.0',
        ]);

        $response->setCallback(static function () use ($xml): void {
            echo $xml;
        });

        return $response;
    }
}
