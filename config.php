<?php

return [
    /*
     * The route prefix, e.g. http://localhost:8080/odata
     */
    'prefix' => env('ODATA_PREFIX', 'odata'),

    /*
     * Whether the package registers its own routes. Set to false when a host
     * application provides its own route group that re-uses the package's
     * routes/odata.php with its own prefix and middleware.
     */
    'register_routes' => true,

    /*
     * Middleware applied to all OData routes registered by this package.
     * Ignored when register_routes is false — the host controls middleware.
     */
    'middleware' => [],

    /*
     * Whether to use streaming JSON responses.
     * @link https://docs.oasis-open.org/odata/odata-json-format/v4.01/odata-json-format-v4.01.html#sec_PayloadOrderingConstraints
     */
    'streaming' => true,

    /*
     * The default XML namespace for the $metadata document.
     */
    'namespace' => env('ODATA_NAMESPACE', 'io.pragmatiqu'),

    /*
     * The OData protocol version advertised in $metadata.
     */
    'version' => env('ODATA_VERSION', '4.0'),

    /*
     * The ODataServiceRegistryInterface implementation to use.
     *
     * The default ODataServiceRegistry serves a single service for the whole
     * HTTP space. Implementors can provide their own class to route different
     * URL paths to different ODataService subclasses.
     */
    'service_registry' => LaravelUi5\OData\ODataServiceRegistry::class,

    /*
     * The ReadAuthorizerInterface implementation to use.
     *
     * OData is security-agnostic. The default AllowAllReadAuthorizer records no
     * verdict, so every read proceeds — an authenticated actor who reaches a URL
     * gets the rows, exactly as before. A host that knows about actors and
     * permissions binds its own enforcer here (or rebinds the interface) to gate
     * reads: a hard denial → 403; a dropped $expand → pruned + a sap-messages warning.
     */
    'read_authorizer' => LaravelUi5\OData\Service\AllowAllReadAuthorizer::class,

    /*
     * Server-driven pagination.
     */
    'pagination' => [
        /*
         * Maximum page size this service will return. null = no limit.
         * Clamps client Prefer: odata.maxpagesize=N to this ceiling.
         */
        'max' => null,

        /*
         * Default page size when the client sends no Prefer header. null = no default.
         */
        'default' => 200,
    ],
];
