<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Contracts;

/**
 * Contract for an addressable OData service endpoint.
 *
 * This is the single service interface for the entire package. Both the
 * legacy engine (Controller\Engine) and the new engine (Protocol\Execution\Engine)
 * consume it.  The protected hooks configure() and bindResolvers() are
 * extension points on ODataService, not part of this public contract.
 */
interface ODataServiceInterface
{
    /**
     * The URI segment that addresses this service below the OData prefix.
     *
     * An empty string means the service is mounted at the prefix root itself.
     */
    public function serviceUri(): string;

    /**
     * The fully qualified URL of this service's root endpoint, including
     * the trailing slash, e.g. "https://example.com/odata/partners/".
     */
    public function endpoint(): string;

    /**
     * The Laravel route path for this service, e.g. "odata/partners".
     */
    public function route(): string;

    /**
     * The XML namespace used in the $metadata document,
     * e.g. "MyService.Data".
     */
    public function namespace(): string;

    /**
     * The absolute filesystem path to a pre-built EDMX file (e.g. from CAPire),
     * or null for dynamic generation.
     */
    public function cachedMetadataXMLPath(): ?string;

    /**
     * Returns the fully resolved runtime schema for this service.
     *
     * Implementations must cache the result after the first call — schema
     * construction (discovery + resolver binding) must not repeat per request.
     */
    public function schema(): RuntimeSchemaInterface;
}
