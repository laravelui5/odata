<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Contracts;

/**
 * Contract for an addressable OData service endpoint.
 *
 * This is the single service interface for the entire package; the engine
 * ({@see \LaravelUi5\OData\Protocol\Execution\Engine}) consumes it. The protected hooks configure() and bindResolvers() are
 * extension points on ODataService, not part of this public contract.
 */
// History: during the lodata migration both the legacy Controller\Engine and the new engine consumed it.
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
     * Where the finished `$metadata` document of this service lives, or null to serialize it per request.
     *
     * `odata:cache` writes the document there, `odata:clear` removes it, and `$metadata` is served from the file
     * whenever it exists — the cache hit; without it the service serializes, the cold path. A service that ships a
     * hand-written document returns its path.
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
