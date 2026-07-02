<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Contracts;

use Illuminate\Database\Query\Builder;
use LaravelUi5\OData\Http\CustomQueryOptions;

/**
 * Formal contract for SQL-backed entity set data sources.
 *
 * Implementations provide the base query for an entity set, including any
 * implicit filters (tenant scoping, user permissions, etc.). OData system
 * query options ($filter, $orderby, $top, $skip) are applied on top by the
 * resolver — they never override the base query's constraints.
 *
 * Implementations are resolved from the Laravel container so that dependencies
 * (e.g., the current tenant or authenticated user) are injected automatically.
 *
 * Example:
 *
 *   final readonly class PartnerValueHelpSource implements EntitySetSourceInterface
 *   {
 *       public function __construct(private TenantContext $tenant) {}
 *
 *       public function query(CustomQueryOptions $options): Builder
 *       {
 *           return DB::table('partner_value_help')
 *               ->where('tenant_id', $this->tenant->id);
 *       }
 *   }
 */
interface EntitySetSourceInterface
{
    /**
     * Return the base query for this entity set.
     *
     * The returned builder must be a fresh instance on each call — it will be
     * mutated by the resolver when applying OData system query options
     * (`$filter`/`$orderby`/`$top`/`$skip`) on top.
     *
     * @param CustomQueryOptions $options The request's custom (non-`$`) query
     *   options, threaded from the URL. Correct under `$batch` (each inner
     *   request carries its own). Sources that don't use them ignore the arg.
     */
    public function query(CustomQueryOptions $options): Builder;
}
