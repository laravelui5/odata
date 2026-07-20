<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Http\Controller;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaravelUi5\OData\Exception\BadRequestException;
use LaravelUi5\OData\Exception\ForbiddenException;
use LaravelUi5\OData\Exception\InternalServerErrorException;
use LaravelUi5\OData\Exception\NotImplementedException;
use LaravelUi5\OData\Exception\ProtocolException;
use LaravelUi5\OData\Http\CustomQueryOptions;
use LaravelUi5\OData\Http\ODataRequest;
use LaravelUi5\OData\Http\ODataResponse;
use LaravelUi5\OData\Protocol\Execution\BatchHandler;
use LaravelUi5\OData\Protocol\Execution\Engine;
use LaravelUi5\OData\Protocol\Planning\QueryPlanner;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Service\Contracts\ReadAuthorizerInterface;
use LaravelUi5\OData\Service\ReadContext;
use Throwable;

/**
 * OData HTTP controller — routes requests through the execution engine.
 *
 * @package LaravelUi5\OData\Controller
 */
class OData extends Controller
{
    public function __construct(private readonly ReadAuthorizerInterface $readAuthorizer)
    {
    }

    /**
     * Handle an OData request, resolving the service from the registry.
     *
     * The registry-backed entry point: one route group, one middleware pipeline,
     * services selected by path. Delegates to {@see self::forService()}.
     */
    public function handle(Request $request, ODataServiceRegistryInterface $resolver): ODataResponse
    {
        return $this->forService($request, $resolver->resolve($request->path()));
    }

    /**
     * Handle an OData request against an already-resolved service.
     *
     * The registry-independent seam: compose your own route, choose your own
     * middleware pipeline, and bind a specific service — e.g. a curated, Basic-auth
     * endpoint for Excel/Power BI beside the standard registry-resolved `/odata` space:
     *
     *     Route::any('excel/{path?}', fn (Request $r) =>
     *         app(OData::class)->forService($r, app(ExcelService::class))
     *     )->where('path', '.*')->middleware('auth.basic');
     *
     * The service declares its own mount via route()/endpoint(). When mounting a
     * service on a non-standard prefix, override BOTH so path-stripping (route()) AND
     * the self-referential URLs — @odata.context, @odata.nextLink (endpoint()) — follow
     * that prefix; otherwise paginated responses emit next-links into the default
     * `/odata` namespace and downstream clients page into the wrong place.
     */
    public function forService(Request $request, ODataServiceInterface $service): ODataResponse
    {
        try {
            $route   = $service->route();
            $rawPath = '/' . ltrim($request->path(), '/');
            $path    = substr($rawPath, strlen('/' . ltrim($route, '/'))) ?: '/';

            // Read-only engine: only GET, HEAD (service root) and POST ($batch) are accepted.
            $method = strtoupper($request->getMethod());

            // HEAD on service root: return CSRF token for UI5 security handshake.
            if ($method === 'HEAD' && trim($path, '/') === '') {
                return new ODataResponse(status: 200, headers: [
                    'X-CSRF-Token' => csrf_token(),
                ]);
            }

            if ($method !== 'GET' && !($method === 'POST' && trim($path, '/') === '$batch')) {
                throw new BadRequestException(
                    'method_not_allowed',
                    sprintf('HTTP method %s is not supported on this read-only service.', $method)
                );
            }

            // Reject unsupported system query options.
            $this->validateQueryOptions($request);

            // Batch — handled separately since it re-dispatches inner requests.
            // Supports both JSON batch and multipart/mixed batch formats.
            if (trim($path, '/') === '$batch') {
                $schema = $service->schema();
                return (new BatchHandler($schema, $service))
                    ->handle($request->getContent(), $request->header('Content-Type'));
            }

            // Resolve page size: client Prefer header → server default → server max.
            $maxPageSize = $this->resolveMaxPageSize($request);

            $planRequest = new ODataRequest(
                path:        $path,
                filter:      $request->query('$filter'),
                select:      $request->query('$select'),
                orderBy:     $request->query('$orderby'),
                top:         $request->query('$top') !== null ? (int) $request->query('$top') : null,
                skip:        $request->query('$skip') !== null ? (int) $request->query('$skip') : null,
                expand:      $request->query('$expand'),
                search:      $request->query('$search'),
                compute:     $request->query('$compute'),
                count:       $request->query('$count') === 'true',
                maxPageSize: $maxPageSize,
                customQueryOptions: CustomQueryOptions::fromQuery($request->query()),
            );

            $schema = $service->schema();
            $plan   = (new QueryPlanner)->plan($planRequest, $schema);

            // Read-authorization forward-exit. The host's authorizer records verdicts into the
            // ReadContext; a hard denial (a primary/root target) answers a 403. The default
            // AllowAll records nothing, so unconfigured OData proceeds unchanged. ($expand
            // drops + the sap-messages partial response arrive in the next slice.)
            $read = new ReadContext();
            $this->readAuthorizer->authorize($plan, $request, $read);

            if ($read->hasHardDenial()) {
                throw ForbiddenException::fromContext($read);
            }

            return (new Engine($schema, $service->endpoint()))->execute($plan);
        } catch (ProtocolException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new InternalServerErrorException('internal_error', $e->getMessage(), $e);
        }
    }

    /**
     * Resolve the effective max page size from the client Prefer header
     * and the server-side pagination config.
     */
    private function resolveMaxPageSize(Request $request): ?int
    {
        // 1. Parse client preference from Prefer header.
        $maxPageSize = null;
        $prefer = $request->header('Prefer', '');
        if ($prefer !== '' && $prefer !== null) {
            if (preg_match('/(?:odata\.)?maxpagesize\s*=\s*(\d+)/i', $prefer, $m)) {
                $maxPageSize = (int) $m[1];
            }
        }

        // 2. Apply server-side default when client sends no preference.
        $paginationDefault = config('odata.pagination.default');
        if ($maxPageSize === null && $paginationDefault !== null) {
            $maxPageSize = (int) $paginationDefault;
        }

        // 3. Clamp to server-side maximum.
        $paginationMax = config('odata.pagination.max');
        if ($paginationMax !== null && ($maxPageSize === null || $maxPageSize > (int) $paginationMax)) {
            $maxPageSize = (int) $paginationMax;
        }

        return $maxPageSize;
    }

    /**
     * Reject unknown $-prefixed query options and unsupported features.
     */
    private function validateQueryOptions(Request $request): void
    {
        $supported = [
            '$filter', '$select', '$orderby', '$top', '$skip', '$count',
            '$expand', '$search', '$compute', '$format', '$skiptoken',
            '$batch',
        ];

        foreach ($request->query() as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            if (str_starts_with($key, '$') && !in_array(strtolower($key), $supported, true)) {
                if (strtolower($key) === '$apply') {
                    throw new NotImplementedException(
                        'not_implemented',
                        'The $apply query option is not supported'
                    );
                }
                throw new BadRequestException(
                    'invalid_query_option',
                    sprintf('Unknown system query option: %s', $key)
                );
            }
        }
    }

    /**
     * @param  string  $method
     * @param  array  $parameters
     */
    public function callAction($method, $parameters)
    {
        return parent::callAction($method, array_values($parameters));
    }
}
