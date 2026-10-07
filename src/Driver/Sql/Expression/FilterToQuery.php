<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Driver\Sql\Expression;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use LaravelUi5\OData\Protocol\Planning\Expression\LambdaExpression;

/**
 * `$filter` → WHERE on a Query\Builder (custom SQL entity sets).
 *
 * A SQL source has no relations to follow, so `any`/`all` are refused (501) rather than
 * dropped; the Eloquent path translates them. Everything else is shared
 * ({@see AbstractFilterTranslator}).
 */
final class FilterToQuery extends AbstractFilterTranslator
{
    public function __construct(QueryBuilder $builder)
    {
        parent::__construct($builder);
    }

    protected function nested(QueryBuilder|EloquentBuilder $builder): static
    {
        return new self($builder);
    }

    public function visitLambda(LambdaExpression $node): mixed
    {
        throw self::unsupported('any()/all() on a custom entity set, which has no relations to follow');
    }
}
