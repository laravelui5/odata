<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Driver\Sql;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use LaravelUi5\OData\Driver\Sql\Expression\FilterToEloquent;
use LaravelUi5\OData\Protocol\Planning\EntitySetQueryPlan;
use LaravelUi5\OData\Protocol\Planning\ExpandList;
use LaravelUi5\OData\Protocol\Planning\Expression\PropertyPathExpression;
use LaravelUi5\OData\Protocol\Planning\NavigationAnchor;
use LaravelUi5\OData\Protocol\Planning\OrderDirection;
use LaravelUi5\OData\Protocol\Planning\PropertySelectItem;
use LaravelUi5\OData\Protocol\Planning\EntityQueryPlan;
use LaravelUi5\OData\Service\Contracts\EntityResolverInterface;
use LaravelUi5\OData\Service\Contracts\EntitySetResolverInterface;
use LaravelUi5\OData\Service\Contracts\QueryPlanInterface;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaInterface;
use LaravelUi5\OData\Service\Contracts\VirtualExpandResolverInterface;

/**
 * Resolves entity-set and single-entity plans against an Eloquent model class.
 *
 * Implements EntitySetResolverInterface for collection queries and
 * EntityResolverInterface for single-entity key lookups. Both are bound
 * from the same model class, so registering one resolver per entity set
 * covers both access patterns without additional wiring.
 */
final class EloquentEntitySetResolver implements EntitySetResolverInterface, EntityResolverInterface
{
    private ?RuntimeSchemaInterface $schema = null;

    /**
     * @param class-string<Model> $modelClass
     */
    public function __construct(private readonly string $modelClass) {}

    /**
     * @return class-string<Model>
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * Set the runtime schema for virtual expand resolution.
     *
     * Called by the RuntimeSchemaBuilder after all resolvers are registered,
     * so Eloquent resolvers can look up virtual expand resolvers by target set.
     */
    public function setSchema(RuntimeSchemaInterface $schema): void
    {
        $this->schema = $schema;
    }

    /**
     * @param QueryPlanInterface $plan  At runtime always EntitySetQueryPlan.
     * @return \Generator<array<string, mixed>>
     */
    public function resolve(QueryPlanInterface $plan): \Generator
    {
        /** @var EntitySetQueryPlan $plan */

        // When the plan has a NavigationAnchor, resolve the intermediate chain
        // to get the parent model, then use its relationship as the query base.
        if ($plan->anchor !== null) {
            $parent = $this->resolveAnchor($plan->anchor);
            if ($parent === null) {
                return;
            }
            $relation = $parent->{$plan->anchor->finalNav}();
            $query = $relation->getQuery();
            // BelongsToMany joins the pivot table; SELECT * would include pivot
            // columns (including its own `id`) that collide with the target table's
            // columns. Qualify the select to avoid column-name collisions.
            if ($relation instanceof BelongsToMany) {
                $query->select($relation->getRelated()->getTable() . '.*');
            }
        } else {
            $query = ($this->modelClass)::query();
        }

        $this->applyFilter($query, $plan);
        $this->applySearch($query, $plan);
        $this->applySelect($query, $plan);
        $this->applyOrderBy($query, $plan);
        $this->applyPagination($query, $plan);
        if ($plan->expand->isEmpty()) {
            foreach ($query->cursor() as $model) {
                $row = $model->toArray();
                yield $this->applyCompute($row, $plan);
            }
        } else {
            // Eager loading requires get() — cursor() does not support with().
            $this->applyExpand($query, $plan->expand);
            foreach ($query->get() as $model) {
                $row = $model->toArray();
                $row = $this->attachExpandedRelations($row, $model, $plan->expand);
                yield $this->applyCompute($row, $plan);
            }
        }
    }

    /**
     * @param QueryPlanInterface $plan  At runtime always EntityQueryPlan.
     * @return array<string, mixed>|null
     */
    public function resolveOne(QueryPlanInterface $plan): ?array
    {
        /** @var EntityQueryPlan $plan */

        if ($plan->anchor !== null) {
            $parent = $this->resolveAnchor($plan->anchor);
            if ($parent === null) {
                return null;
            }
            $relation = $parent->{$plan->anchor->finalNav}();
            $query = $relation->getQuery();
            if ($relation instanceof BelongsToMany) {
                $query->select($relation->getRelated()->getTable() . '.*');
            }
        } else {
            $query = ($this->modelClass)::query();
        }

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
            // When expanding, always include the PK for relation matching.
            if (!$plan->expand->isEmpty()) {
                $keyName = (new ($this->modelClass))->getKeyName();
                if (!in_array($keyName, $columns, true)) {
                    $columns[] = $keyName;
                }
            }
            if ($columns !== []) {
                $query->select($columns);
            }
        }

        $this->applyExpand($query, $plan->expand);

        $model = $query->first();
        if ($model === null) {
            return null;
        }

        $row = $model->toArray();
        return $this->attachExpandedRelations($row, $model, $plan->expand);
    }

    /**
     * @param QueryPlanInterface $plan  At runtime always EntitySetQueryPlan.
     */
    public function count(QueryPlanInterface $plan): int
    {
        /** @var EntitySetQueryPlan $plan */

        if ($plan->anchor !== null) {
            $parent = $this->resolveAnchor($plan->anchor);
            if ($parent === null) {
                return 0;
            }
            $relation = $parent->{$plan->anchor->finalNav}();
            $query = $relation->getQuery();
            if ($relation instanceof BelongsToMany) {
                $query->select($relation->getRelated()->getTable() . '.*');
            }
        } else {
            $query = ($this->modelClass)::query();
        }

        $this->applyFilter($query, $plan);
        $this->applySearch($query, $plan);

        return $query->count();
    }

    /**
     * Resolve a NavigationAnchor by loading the root entity and following
     * intermediate single-entity navigations to reach the parent model.
     *
     * Returns null if the root entity or any intermediate entity is not found.
     */
    private function resolveAnchor(NavigationAnchor $anchor): ?Model
    {
        // Load the root entity from its resolver's model class.
        $rootResolver = $this->schema?->getResolver($anchor->rootSet);
        if (!$rootResolver instanceof self) {
            return null;
        }

        $rootModelClass = $rootResolver->getModelClass();
        $rootQuery = $rootModelClass::query();
        foreach ($anchor->rootKey->values as $column => $literal) {
            $rootQuery->where($column, '=', $literal->value);
        }

        $current = $rootQuery->first();
        if ($current === null) {
            return null;
        }

        // Follow each intermediate navigation step.
        foreach ($anchor->steps as $navName) {
            $current = $current->$navName;
            if ($current === null) {
                return null;
            }
        }

        return $current;
    }

    private function applyExpand(Builder $query, ExpandList $expand): void
    {
        if ($expand->isEmpty()) {
            return;
        }

        $withs = $this->collectEagerLoads($expand, '', $this->modelClass);
        $query->with($withs);
    }

    /**
     * Recursively collect Eloquent eager-load definitions from the expand tree.
     *
     * Uses dot-notation for nested relations (e.g. "contact_project.contact").
     *
     * @param class-string<Model> $modelClass The model class at the current nesting depth.
     * @return array<int|string, string|callable>
     */
    private function collectEagerLoads(ExpandList $expand, string $prefix, string $modelClass): array
    {
        $withs = [];

        foreach ($expand->items as $item) {
            $navName  = $item->property->getName();
            $fullPath = $prefix !== '' ? $prefix . '.' . $navName : $navName;

            // Skip virtual navigation properties — they're handled in attachExpandedRelations()
            if (!method_exists($modelClass, $navName)) {
                continue;
            }

            $hasConstraints = $item->filter !== null || !$item->select->isSelectAll()
                || $item->orderBy !== null || $item->top !== null || $item->skip !== null;

            if (!$hasConstraints) {
                $withs[] = $fullPath;
            } else {
                // Constrained expand — pass a closure to with().
                $withs[$fullPath] = function ($relQuery) use ($item) {
                    if ($item->filter !== null) {
                        $relQuery->where(function ($q) use ($item) {
                            (new FilterToEloquent($q))->apply($item->filter);
                        });
                    }

                    if (!$item->select->isSelectAll()) {
                        $columns = [];
                        foreach ($item->select->items as $selectItem) {
                            if ($selectItem instanceof PropertySelectItem) {
                                $columns[] = $selectItem->property->getName();
                            }
                        }
                        // Always include PK + FK for relation matching.
                        if ($columns !== []) {
                            // For BelongsToMany, qualify the PK with the table name
                            // to avoid ambiguity with the pivot table's own `id`.
                            $model = $relQuery->getModel();
                            $keyName = $model->getKeyName();
                            $columns[] = $relQuery instanceof BelongsToMany
                                ? $model->getTable() . '.' . $keyName
                                : $keyName;
                            if (method_exists($relQuery, 'getForeignKeyName')
                                && !$relQuery instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                                $columns[] = $relQuery->getForeignKeyName();
                            }
                            // Include FK columns needed by nested BelongsTo expands,
                            // otherwise the nested relation cannot be matched.
                            if (!$item->expand->isEmpty()) {
                                $relatedModel = $relQuery->getModel();
                                foreach ($item->expand->items as $nestedItem) {
                                    $nestedNavName = $nestedItem->property->getName();
                                    if (method_exists($relatedModel, $nestedNavName)) {
                                        $nestedRel = $relatedModel->$nestedNavName();
                                        if ($nestedRel instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                                            $columns[] = $nestedRel->getForeignKeyName();
                                        }
                                    }
                                }
                            }
                            $relQuery->select(array_unique($columns));
                        }
                    }

                    if ($item->orderBy !== null) {
                        foreach ($item->orderBy->items as $orderItem) {
                            if ($orderItem->expression instanceof PropertyPathExpression) {
                                $segs = $orderItem->expression->segments;
                                $col  = $segs[count($segs) - 1]->getName();
                                $dir  = $orderItem->direction === OrderDirection::Desc ? 'desc' : 'asc';
                                $relQuery->orderBy($col, $dir);
                            }
                        }
                    }

                    if ($item->top !== null) {
                        $relQuery->limit($item->top);
                    }

                    if ($item->skip !== null) {
                        $relQuery->skip($item->skip);
                        if ($item->top === null) {
                            $relQuery->limit(PHP_INT_MAX);
                        }
                    }
                };
            }

            // Recurse into nested expands.
            if (!$item->expand->isEmpty()) {
                // Resolve the related model class for the next depth.
                $relatedModel = (new $modelClass)->$navName()->getRelated();
                $nested = $this->collectEagerLoads($item->expand, $fullPath, get_class($relatedModel));
                foreach ($nested as $key => $value) {
                    if (is_int($key)) {
                        $withs[] = $value;
                    } else {
                        $withs[$key] = $value;
                    }
                }
            }
        }

        return $withs;
    }

    /**
     * Move Eloquent-loaded relations into the row array under the nav property name.
     *
     * @return array<string, mixed>
     */
    private function attachExpandedRelations(array $row, Model $model, ExpandList $expand): array
    {
        $entityTypeName = (new \ReflectionClass($model))->getShortName();

        foreach ($expand->items as $item) {
            $navName = $item->property->getName();

            // Virtual navigation property — delegate to its custom resolver
            if (!method_exists($model, $navName)) {
                $row[$navName] = $this->resolveVirtualExpand($row, $entityTypeName, $item);
                continue;
            }

            $relation = $model->getRelation($navName);

            if ($item->property->isCollection()) {
                if ($item->expand->isEmpty()) {
                    $row[$navName] = $relation?->map(fn(Model $m) => $m->toArray())->all() ?? [];
                } else {
                    $row[$navName] = $relation?->map(
                        fn(Model $m) => $this->attachExpandedRelations($m->toArray(), $m, $item->expand)
                    )->all() ?? [];
                }
            } else {
                if ($relation === null) {
                    $row[$navName] = null;
                } elseif ($item->expand->isEmpty()) {
                    $row[$navName] = $relation->toArray();
                } else {
                    $row[$navName] = $this->attachExpandedRelations($relation->toArray(), $relation, $item->expand);
                }
            }
        }

        return $row;
    }

    /**
     * Resolve a virtual navigation expand by delegating to the target entity set's resolver.
     *
     * @return list<array<string, mixed>>
     */
    private function resolveVirtualExpand(array $parentRow, string $parentEntityType, \LaravelUi5\OData\Protocol\Planning\ExpandItem $item): array
    {
        if ($this->schema === null) {
            return [];
        }

        $resolver = $this->schema->getResolver($item->targetSet);

        if ($resolver instanceof VirtualExpandResolverInterface) {
            return $resolver->resolveExpand($parentRow, $parentEntityType, $item);
        }

        return [];
    }

    private function applySearch(Builder $query, EntitySetQueryPlan $plan): void
    {
        if ($plan->search === null || $plan->search === '') {
            return;
        }

        // Simple search: LIKE '%term%' on all string properties.
        $term = trim($plan->search, '"\'');
        $entityType = $plan->target->getEntityType();
        $stringColumns = [];

        foreach ($entityType->getDeclaredProperties() as $prop) {
            $type = $prop->getType();
            if ($type instanceof \LaravelUi5\OData\Edm\Type\PrimitiveType) {
                if ($type->getPrimitiveType() === \LaravelUi5\OData\Edm\EdmPrimitiveType::String) {
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

    /**
     * Simple expression evaluator for $compute.
     *
     * Supports: property references, concat(a, b), arithmetic (add, sub, mul, div),
     * string literals, numeric literals.
     */
    private function evaluateComputeExpression(string $expression, array $row): mixed
    {
        $expr = trim($expression);

        // concat(expr1, expr2)
        if (preg_match('/^concat\((.+)\)$/i', $expr, $m)) {
            $args = $this->splitComputeArgs($m[1]);
            $parts = array_map(fn($a) => (string) $this->evaluateComputeExpression(trim($a), $row), $args);
            return implode('', $parts);
        }

        // year(prop), month(prop), day(prop)
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

        // tolower(expr), toupper(expr)
        if (preg_match('/^(tolower|toupper)\((.+)\)$/i', $expr, $m)) {
            $inner = (string) $this->evaluateComputeExpression(trim($m[2]), $row);
            return strtolower($m[1]) === 'tolower' ? strtolower($inner) : strtoupper($inner);
        }

        // Arithmetic: expr add|sub|mul|div expr
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

        // String literal: 'value'
        if (str_starts_with($expr, "'") && str_ends_with($expr, "'")) {
            return substr($expr, 1, -1);
        }

        // Numeric literal
        if (is_numeric($expr)) {
            return str_contains($expr, '.') ? (float) $expr : (int) $expr;
        }

        // Property reference
        return $row[$expr] ?? null;
    }

    /**
     * Split comma-separated arguments respecting parentheses nesting.
     *
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

    private function applyFilter(Builder $query, EntitySetQueryPlan $plan): void
    {
        if ($plan->filter === null) {
            return;
        }

        $query->where(function (Builder $q) use ($plan): void {
            (new FilterToEloquent($q))->apply($plan->filter);
        });
    }

    private function applySelect(Builder $query, EntitySetQueryPlan $plan): void
    {
        if ($plan->select->isSelectAll()) {
            return;
        }

        // When $compute is present, skip SQL-level select — computed expressions
        // may reference any column. The handler does response-level projection.
        if ($plan->compute !== []) {
            return;
        }

        $columns = [];
        foreach ($plan->select->items as $item) {
            if ($item instanceof PropertySelectItem) {
                $columns[] = $item->property->getName();
            }
        }

        // When expanding, always include the model's key column so Eloquent
        // can match eager-loaded relations to their parent rows.
        if (!$plan->expand->isEmpty()) {
            $keyName = (new ($this->modelClass))->getKeyName();
            if (!in_array($keyName, $columns, true)) {
                $columns[] = $keyName;
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
            // SQLite requires a LIMIT when OFFSET is used; use max int as a sentinel.
            if ($plan->top === null) {
                $query->limit(PHP_INT_MAX);
            }
        }

        if ($plan->top !== null) {
            $query->limit($plan->top);
        }
    }
}
