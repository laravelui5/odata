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
use LaravelUi5\OData\Protocol\Planning\EntityQueryPlan;
use LaravelUi5\OData\Protocol\Planning\EntitySetQueryPlan;
use LaravelUi5\OData\Protocol\Planning\ExpandPruner;
use LaravelUi5\OData\Protocol\Planning\QueryPlan;
use LaravelUi5\OData\Protocol\Planning\QueryPlanner;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Service\Contracts\ReadAuthorizerInterface;
use LaravelUi5\OData\Service\ReadContext;
use LaravelUi5\OData\Service\ReadMessage;
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
            // ReadContext; the default AllowAll records nothing, so unconfigured OData proceeds
            // unchanged.
            $read = new ReadContext();
            $this->readAuthorizer->authorize($plan, $request, $read);

            // A hard denial (a primary/root target) answers a 403.
            if ($read->hasHardDenial()) {
                throw ForbiddenException::fromContext($read);
            }

            // Honest-partial: prune any gated $expand from the plan so the allowed sets still
            // serve (200), and report each drop in a sap-messages header. Denials are known at
            // plan time, so the header is set before the response streams.
            if ($read->dropped() !== []) {
                $plan = $this->pruneDroppedExpands($plan, $read->dropped());
            }

            $response = (new Engine($schema, $service->endpoint()))->execute($plan);

            if ($read->dropMessages() !== []) {
                $response->headers->set('sap-messages', $this->encodeSapMessages($read->dropMessages()));
            }

            return $response;
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
    /**
     * Rebuild the plan without the gated $expand targets. Read authorization is per entity
     * set, so a dropped set name removes every expand pointing at it, at any depth. Plan types
     * that carry no expands are returned unchanged.
     *
     * @param list<string> $droppedSetNames
     */
    private function pruneDroppedExpands(QueryPlan $plan, array $droppedSetNames): QueryPlan
    {
        if ($plan instanceof EntitySetQueryPlan || $plan instanceof EntityQueryPlan) {
            return $plan->withExpand(ExpandPruner::prune($plan->expand, $droppedSetNames));
        }

        return $plan;
    }

    /**
     * Serialize the drop messages as the standard `sap-messages` header value (a JSON array of
     * unbound messages) — the carrier the UI5 v4 model ingests natively.
     *
     * @param list<ReadMessage> $messages
     */
    private function encodeSapMessages(array $messages): string
    {
        return json_encode(
            array_map(static fn (ReadMessage $message) => $message->toArray(), $messages),
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
    }

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
