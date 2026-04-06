<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Driver\Sql;

use Illuminate\Database\Query\Builder;
use LaravelUi5\OData\Driver\Sql\Expression\FilterToQuery;
use LaravelUi5\OData\Edm\Contracts\Container\PrimitiveTypeEnum;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Protocol\Planning\EntityQueryPlan;
use LaravelUi5\OData\Protocol\Planning\EntitySetQueryPlan;
use LaravelUi5\OData\Protocol\Planning\Expression\PropertyPathExpression;
use LaravelUi5\OData\Protocol\Planning\OrderDirection;
use LaravelUi5\OData\Protocol\Planning\PropertySelectItem;
use LaravelUi5\OData\Service\Contracts\EntityResolverInterface;
use LaravelUi5\OData\Service\Contracts\EntitySetResolverInterface;
use LaravelUi5\OData\Service\Contracts\EntitySetSourceInterface;
use LaravelUi5\OData\Service\Contracts\QueryPlanInterface;

/**
 * Resolves entity-set and single-entity plans against a SQL data source.
 *
 * The data source is provided via {@see EntitySetSourceInterface}, which
 * supplies a fresh Query Builder on each call. This keeps the resolver
 * decoupled from how the query is constructed (table, view, subquery,
 * tenant-scoped, etc.).
 */
readonly class SqlEntitySetResolver implements EntitySetResolverInterface, EntityResolverInterface
{
    public function __construct(private EntitySetSourceInterface $source) {}

    /**
     * @param QueryPlanInterface $plan  At runtime always EntitySetQueryPlan.
     * @return \Generator<array<string, mixed>>
     */
    public function resolve(QueryPlanInterface $plan): \Generator
    {
        /** @var EntitySetQueryPlan $plan */
        $query = $this->baseQuery();

        $this->applyFilter($query, $plan);
        $this->applySearch($query, $plan);
        $this->applySelect($query, $plan);
        $this->applyOrderBy($query, $plan);
        $this->applyPagination($query, $plan);

        foreach ($query->cursor() as $row) {
            $row = (array) $row;
            yield $this->applyCompute($row, $plan);
        }
    }

    /**
     * @param QueryPlanInterface $plan  At runtime always EntityQueryPlan.
     * @return array<string, mixed>|null
     */
    public function resolveOne(QueryPlanInterface $plan): ?array
    {
        /** @var EntityQueryPlan $plan */
        $query = $this->baseQuery();

        foreach ($plan->key->values as $column => $literal) {
            $query->where($column, '=', $literal->value);
        }

        if (!$plan->select->isSelectAll()) {
            $columns = [];
            foreach ($plan->select->items as $item) {
                if ($item instanceof PropertySelectItem) {
                    $columns[] = $item->property->getName();
                }
            }
            if ($columns !== []) {
                $query->select($columns);
            }
        }

        $row = $query->first();
        return $row !== null ? (array) $row : null;
    }

    /**
     * @param QueryPlanInterface $plan  At runtime always EntitySetQueryPlan.
     */
    public function count(QueryPlanInterface $plan): int
    {
        /** @var EntitySetQueryPlan $plan */
        $query = $this->baseQuery();

        $this->applyFilter($query, $plan);
        $this->applySearch($query, $plan);

        return $query->count();
    }

    // ── Internal ─────────────────────────────────────────────────────────────

    private function baseQuery(): Builder
    {
        return $this->source->query();
    }

    private function applyFilter(Builder $query, EntitySetQueryPlan $plan): void
    {
        if ($plan->filter === null) {
            return;
        }

        $query->where(function (Builder $q) use ($plan): void {
            (new FilterToQuery($q))->apply($plan->filter);
        });
    }

    private function applySearch(Builder $query, EntitySetQueryPlan $plan): void
    {
        if ($plan->search === null || $plan->search === '') {
            return;
        }

        $term = trim($plan->search, '"\'');
        $entityType = $plan->target->getEntityType();
        $stringColumns = [];

        foreach ($entityType->getDeclaredProperties() as $prop) {
            $type = $prop->getType();
            if ($type instanceof PrimitiveType) {
                if ($type->getPrimitiveType() === PrimitiveTypeEnum::String) {
                    $stringColumns[] = $prop->getName();
                }
            }
        }

        if ($stringColumns === []) {
            return;
        }

        $query->where(function (Builder $q) use ($stringColumns, $term) {
            foreach ($stringColumns as $col) {
                $q->orWhere($col, 'LIKE', '%' . $term . '%');
            }
        });
    }

    private function applySelect(Builder $query, EntitySetQueryPlan $plan): void
    {
        if ($plan->select->isSelectAll()) {
            return;
        }

        if ($plan->compute !== []) {
            return;
        }

        $columns = [];
        foreach ($plan->select->items as $item) {
            if ($item instanceof PropertySelectItem) {
                $columns[] = $item->property->getName();
            }
        }

        if ($columns !== []) {
            $query->select($columns);
        }
    }

    private function applyOrderBy(Builder $query, EntitySetQueryPlan $plan): void
    {
        foreach ($plan->orderBy->items as $item) {
            if (!($item->expression instanceof PropertyPathExpression)) {
                continue;
            }

            $segments = $item->expression->segments;
            $column   = $segments[count($segments) - 1]->getName();
            $direction = $item->direction === OrderDirection::Desc ? 'desc' : 'asc';
            $query->orderBy($column, $direction);
        }
    }

    private function applyPagination(Builder $query, EntitySetQueryPlan $plan): void
    {
        if ($plan->skip !== null) {
            $query->skip($plan->skip);
            if ($plan->top === null) {
                $query->limit(PHP_INT_MAX);
            }
        }

        if ($plan->top !== null) {
            $query->limit($plan->top);
        }
    }

    /**
     * Evaluate $compute expressions and add computed properties to the row.
     *
     * @return array<string, mixed>
     */
    private function applyCompute(array $row, EntitySetQueryPlan $plan): array
    {
        if ($plan->compute === []) {
            return $row;
        }

        foreach ($plan->compute as $computed) {
            $row[$computed->alias] = $this->evaluateComputeExpression($computed->expression, $row);
        }

        return $row;
    }

    private function evaluateComputeExpression(string $expression, array $row): mixed
    {
        $expr = trim($expression);

        if (preg_match('/^concat\((.+)\)$/i', $expr, $m)) {
            $args = $this->splitComputeArgs($m[1]);
            $parts = array_map(fn($a) => (string) $this->evaluateComputeExpression(trim($a), $row), $args);
            return implode('', $parts);
        }

        if (preg_match('/^(year|month|day)\((.+)\)$/i', $expr, $m)) {
            $fn    = strtolower($m[1]);
            $inner = $this->evaluateComputeExpression(trim($m[2]), $row);
            if ($inner === null) {
                return null;
            }
            $date = new \DateTimeImmutable((string) $inner);
            return match ($fn) {
                'year'  => (int) $date->format('Y'),
                'month' => (int) $date->format('m'),
                'day'   => (int) $date->format('d'),
            };
        }

        if (preg_match('/^(tolower|toupper)\((.+)\)$/i', $expr, $m)) {
            $inner = (string) $this->evaluateComputeExpression(trim($m[2]), $row);
            return strtolower($m[1]) === 'tolower' ? strtolower($inner) : strtoupper($inner);
        }

        if (preg_match('/^(.+)\s+(add|sub|mul|div)\s+(.+)$/', $expr, $m)) {
            $left  = $this->evaluateComputeExpression(trim($m[1]), $row);
            $right = $this->evaluateComputeExpression(trim($m[3]), $row);
            return match ($m[2]) {
                'add' => $left + $right,
                'sub' => $left - $right,
                'mul' => $left * $right,
                'div' => $right != 0 ? $left / $right : null,
            };
        }

        if (str_starts_with($expr, "'") && str_ends_with($expr, "'")) {
            return substr($expr, 1, -1);
        }

        if (is_numeric($expr)) {
            return str_contains($expr, '.') ? (float) $expr : (int) $expr;
        }

        return $row[$expr] ?? null;
    }

    /**
     * @return list<string>
     */
    private function splitComputeArgs(string $input): array
    {
        $args    = [];
        $current = '';
        $depth   = 0;

        for ($i = 0, $len = strlen($input); $i < $len; $i++) {
            $ch = $input[$i];
            if ($ch === '(') {
                $depth++;
            } elseif ($ch === ')') {
                $depth--;
            } elseif ($ch === ',' && $depth === 0) {
                $args[]  = $current;
                $current = '';
                continue;
            }
            $current .= $ch;
        }

        if ($current !== '') {
            $args[] = $current;
        }

        return $args;
    }
}
