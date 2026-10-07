<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Driver\Sql\Expression;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use LaravelUi5\OData\Protocol\Planning\Expression\LambdaExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\LambdaOperator;

/**
 * `$filter` → WHERE on an Eloquent Builder (discovered models).
 *
 * Adds `any`/`all` over relations (`whereHas` / `whereDoesntHave`); everything else is
 * shared ({@see AbstractFilterTranslator}).
 */
final class FilterToEloquent extends AbstractFilterTranslator
{
    public function __construct(EloquentBuilder $builder)
    {
        parent::__construct($builder);
    }

    protected function nested(QueryBuilder|EloquentBuilder $builder): static
    {
        return new self($builder);
    }

    public function visitLambda(LambdaExpression $node): mixed
    {
        $navProperty = $node->collection->segments[0] ?? null;
        if ($navProperty === null) {
            throw self::invalid('any()/all() needs a navigation property');
        }

        $relation = $navProperty->getName();

        if ($node->operator === LambdaOperator::Any) {
            $this->builder->whereHas($relation, fn (EloquentBuilder $q) => $node->predicate->accept(new self($q)));
        } else {
            // all(): no related entity fails the predicate
            $this->builder->whereDoesntHave($relation, fn (EloquentBuilder $q) => $q->whereNot(
                fn (EloquentBuilder $inner) => $node->predicate->accept(new self($inner)),
            ));
        }

        return null;
    }
}
