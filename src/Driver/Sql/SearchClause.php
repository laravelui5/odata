<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Driver\Sql;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Type\PrimitiveTypeInterface;
use LaravelUi5\OData\Edm\EdmPrimitiveType;

/**
 * `$search` as a substring match over the string properties of an entity type —
 * one place for the SQL and the Eloquent resolver, so the two stay alike.
 *
 * The term is matched literally. `%` and `_` are LIKE wildcards, so they are
 * escaped, with `!` as the escape character: a backslash in a SQL string literal
 * means one character to MySQL and two to SQLite and PostgreSQL, while `!` reads
 * the same everywhere. One surrounding pair of quotes is taken as phrase syntax
 * and removed; a quote inside the term, or a lone one at its end, stays.
 */
final class SearchClause
{
    private const string ESCAPE = '!';

    public static function apply(QueryBuilder|EloquentBuilder $query, EntityTypeInterface $type, ?string $search): void
    {
        if ($search === null || $search === '') {
            return;
        }

        $columns = [];
        foreach ($type->getDeclaredProperties() as $property) {
            $propertyType = $property->getType();
            if ($propertyType instanceof PrimitiveTypeInterface && $propertyType->getPrimitiveType() === EdmPrimitiveType::String) {
                $columns[] = $property->getName();
            }
        }

        if ($columns === []) {
            return;
        }

        $pattern = '%' . self::escape(self::term($search)) . '%';
        $grammar = ($query instanceof EloquentBuilder ? $query->getQuery() : $query)->getGrammar();

        $query->where(static function ($q) use ($columns, $pattern, $grammar): void {
            foreach ($columns as $column) {
                $q->orWhereRaw($grammar->wrap($column) . " LIKE ? ESCAPE '" . self::ESCAPE . "'", [$pattern]);
            }
        });
    }

    /** The search term without one surrounding pair of matching quotes. */
    public static function term(string $search): string
    {
        $first = $search[0];
        if (strlen($search) >= 2 && ($first === '"' || $first === "'") && $search[strlen($search) - 1] === $first) {
            return substr($search, 1, -1);
        }
        return $search;
    }

    /** The term with LIKE's wildcards and the escape character itself escaped. */
    public static function escape(string $term): string
    {
        return strtr($term, [
            self::ESCAPE => self::ESCAPE . self::ESCAPE,
            '%'          => self::ESCAPE . '%',
            '_'          => self::ESCAPE . '_',
        ]);
    }
}
