<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Resolver;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Driver\Sql\SqlEntitySetResolver;
use LaravelUi5\OData\Http\CustomQueryOptions;
use LaravelUi5\OData\Service\Contracts\EntitySetResolverInterface;
use LaravelUi5\OData\Service\Contracts\EntitySetSourceInterface;
use LaravelUi5\OData\Service\Contracts\ResolverBindingInterface;

/**
 * Serializable binding for a table-or-view-backed entity set.
 *
 * Stores the table/view name and optional connection. For sources that
 * need implicit filters or dependency injection, use SqlSourceBinding
 * with an EntitySetSourceInterface class instead.
 */
final readonly class SqlBinding implements ResolverBindingInterface, EntitySetSourceInterface
{
    public function __construct(
        public string $table,
        public ?string $connection = null,
    ) {}

    public function query(CustomQueryOptions $options): Builder
    {
        return DB::connection($this->connection)->table($this->table);
    }

    public function createResolver(): EntitySetResolverInterface
    {
        return new SqlEntitySetResolver($this);
    }

    /**
     * A raw table/view has no authored class to reflect on — it is gated by
     * converting it to a SqlSourceBinding with an EntitySetSourceInterface class.
     */
    public function getSourceClass(): ?string
    {
        return null;
    }
}
