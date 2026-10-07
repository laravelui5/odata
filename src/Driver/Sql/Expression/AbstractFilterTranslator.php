<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Driver\Sql\Expression;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use LaravelUi5\OData\Driver\Sql\SearchClause;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Type\PrimitiveTypeInterface;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Exception\BadRequestException;
use LaravelUi5\OData\Exception\NotImplementedException;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryOperator;
use LaravelUi5\OData\Protocol\Planning\Expression\FilterExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\FilterExpressionVisitor;
use LaravelUi5\OData\Protocol\Planning\Expression\FunctionCallExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\LambdaVariableExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\LiteralExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\NullLiteralExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\PropertyPathExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\UnaryExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\UnaryOperator;

/**
 * Translates a `$filter` tree into WHERE clauses — shared by the SQL and the Eloquent path,
 * which differ only in lambdas (`any`/`all` need relations).
 *
 * **What it cannot translate, it refuses.** A filter that silently does not filter is a data
 * exposure, not a missing feature: before, an unsupported function or operator either added no
 * condition (every row) or compared against `""` (no row), and both answered `200`. Now an
 * unsupported construct answers `501 unsupported_filter`, naming it; a malformed comparison
 * answers `400 invalid_filter`.
 *
 * Supported: `eq ne gt ge lt le in and or not`, `contains startswith endswith` (matched
 * literally, wildcards escaped), `tolower`/`toupper` around a property or a string literal,
 * a Boolean property or literal on its own. A comparison needs a property on one side.
 *
 * The visit* methods handle a node in *boolean* position; operands are resolved by
 * {@see operand()}, so a function or arithmetic in operand position never reaches visit*.
 */
abstract class AbstractFilterTranslator implements FilterExpressionVisitor
{
    private const array MIRRORED = ['=' => '=', '<>' => '<>', '>' => '<', '>=' => '<=', '<' => '>', '<=' => '>='];

    public function __construct(protected readonly QueryBuilder|EloquentBuilder $builder) {}

    public function apply(FilterExpression $expression): void
    {
        $expression->accept($this);
    }

    /** A translator of the same kind for a nested group. */
    abstract protected function nested(QueryBuilder|EloquentBuilder $builder): static;

    // ── Boolean position ─────────────────────────────────────────────────────

    public function visitLiteral(LiteralExpression $node): mixed
    {
        // `$filter=true` filters nothing, `$filter=false` everything.
        match ($node->value) {
            true    => null,
            false   => $this->builder->whereRaw('1 = 0'),
            default => throw self::invalid('a literal on its own is not a condition'),
        };

        return null;
    }

    public function visitNullLiteral(NullLiteralExpression $node): mixed
    {
        throw self::invalid('null on its own is not a condition');
    }

    public function visitPropertyPath(PropertyPathExpression $node): mixed
    {
        // A Boolean property on its own: `$filter=is_active`.
        $property = $node->segments[count($node->segments) - 1];
        $type     = $property instanceof PropertyInterface ? $property->getType() : null;

        if (!$type instanceof PrimitiveTypeInterface || $type->getPrimitiveType() !== EdmPrimitiveType::Boolean) {
            throw self::invalid("property {$property->getName()} is not a condition; compare it with something");
        }

        $this->builder->where($property->getName(), '=', true);

        return null;
    }

    public function visitBinary(BinaryExpression $node): mixed
    {
        match ($node->operator) {
            BinaryOperator::And => $this->builder
                ->where(fn ($q) => $node->left->accept($this->nested($q)))
                ->where(fn ($q) => $node->right->accept($this->nested($q))),
            BinaryOperator::Or  => $this->builder
                ->where(fn ($q) => $node->left->accept($this->nested($q)))
                ->orWhere(fn ($q) => $node->right->accept($this->nested($q))),
            BinaryOperator::Eq  => $this->compare($node, '='),
            BinaryOperator::Ne  => $this->compare($node, '<>'),
            BinaryOperator::Gt  => $this->compare($node, '>'),
            BinaryOperator::Ge  => $this->compare($node, '>='),
            BinaryOperator::Lt  => $this->compare($node, '<'),
            BinaryOperator::Le  => $this->compare($node, '<='),
            BinaryOperator::In  => $this->in($node),
            default             => throw self::unsupported("the operator '" . strtolower($node->operator->name) . "'"),
        };

        return null;
    }

    public function visitUnary(UnaryExpression $node): mixed
    {
        if ($node->operator !== UnaryOperator::Not) {
            throw self::unsupported("the operator '" . strtolower($node->operator->name) . "' as a condition");
        }

        $this->builder->whereNot(fn ($q) => $node->operand->accept($this->nested($q)));

        return null;
    }

    public function visitFunctionCall(FunctionCallExpression $node): mixed
    {
        match (strtolower($node->name)) {
            'contains'   => $this->like($node, '%', '%'),
            'startswith' => $this->like($node, '', '%'),
            'endswith'   => $this->like($node, '%', ''),
            default      => throw self::unsupported("the function {$node->name}()"),
        };

        return null;
    }

    public function visitLambdaVariable(LambdaVariableExpression $node): mixed
    {
        throw self::invalid('a lambda variable on its own is not a condition');
    }

    // ── Operands ─────────────────────────────────────────────────────────────

    /**
     * An operand: a column (optionally wrapped in LOWER/UPPER), a value, or null.
     *
     * @return array{kind: 'column', sql: string, raw: bool}|array{kind: 'value', value: mixed}|array{kind: 'null'}
     */
    protected function operand(FilterExpression $expr): array
    {
        if ($expr instanceof PropertyPathExpression) {
            return ['kind' => 'column', 'sql' => $expr->segments[count($expr->segments) - 1]->getName(), 'raw' => false];
        }
        if ($expr instanceof LiteralExpression) {
            return ['kind' => 'value', 'value' => $expr->value];
        }
        if ($expr instanceof NullLiteralExpression) {
            return ['kind' => 'null'];
        }
        if ($expr instanceof FunctionCallExpression
            && in_array(strtolower($expr->name), ['tolower', 'toupper'], true)
            && count($expr->arguments) === 1
        ) {
            $inner = $this->operand($expr->arguments[0]);
            $upper = strtolower($expr->name) === 'toupper';

            return match ($inner['kind']) {
                'column' => ['kind' => 'column', 'sql' => ($upper ? 'UPPER(' : 'LOWER(') . $this->wrap($inner) . ')', 'raw' => true],
                'value'  => ['kind' => 'value', 'value' => is_string($inner['value'])
                    ? ($upper ? mb_strtoupper($inner['value']) : mb_strtolower($inner['value']))
                    : throw self::invalid("{$expr->name}() takes a string")],
                default  => throw self::invalid("{$expr->name}() of null"),
            };
        }

        throw self::unsupported(self::describe($expr) . ' as an operand');
    }

    private function compare(BinaryExpression $node, string $op): void
    {
        $left  = $this->operand($node->left);
        $right = $this->operand($node->right);

        // `3 lt id` → `id gt 3`
        if ($left['kind'] !== 'column' && $right['kind'] === 'column') {
            [$left, $right, $op] = [$right, $left, self::MIRRORED[$op]];
        }

        if ($left['kind'] !== 'column') {
            throw self::invalid('a comparison needs a property on one side');
        }

        if ($right['kind'] === 'null') {
            match ($op) {
                '='     => $this->builder->whereRaw($this->wrap($left) . ' IS NULL'),
                '<>'    => $this->builder->whereRaw($this->wrap($left) . ' IS NOT NULL'),
                default => throw self::invalid('null can only be compared with eq or ne'),
            };
            return;
        }

        if ($right['kind'] === 'column') {
            $this->builder->whereRaw($this->wrap($left) . " {$op} " . $this->wrap($right));
            return;
        }

        $this->builder->whereRaw($this->wrap($left) . " {$op} ?", [$right['value']]);
    }

    private function in(BinaryExpression $node): void
    {
        $left = $this->operand($node->left);
        $list = $node->right;

        if ($left['kind'] !== 'column' || !$list instanceof FunctionCallExpression || $list->name !== '__list') {
            throw self::invalid('in needs a property on the left and a list on the right');
        }

        $values = array_map(function (FilterExpression $item): mixed {
            $operand = $this->operand($item);
            return $operand['kind'] === 'value' ? $operand['value'] : throw self::invalid('an in-list holds literals only');
        }, $list->arguments);

        if ($values === []) {
            $this->builder->whereRaw('1 = 0');
            return;
        }

        $this->builder->whereRaw(
            $this->wrap($left) . ' IN (' . implode(', ', array_fill(0, count($values), '?')) . ')',
            $values,
        );
    }

    private function like(FunctionCallExpression $node, string $prefix, string $suffix): void
    {
        if (count($node->arguments) !== 2) {
            throw self::invalid("{$node->name}() takes two arguments");
        }

        $column = $this->operand($node->arguments[0]);
        $value  = $this->operand($node->arguments[1]);

        if ($column['kind'] !== 'column' || $value['kind'] !== 'value' || !is_string($value['value'])) {
            throw self::unsupported("{$node->name}() other than (property, 'text')");
        }

        // Matched literally: % and _ in the value are not wildcards (as for $search).
        $this->builder->whereRaw(
            $this->wrap($column) . " LIKE ? ESCAPE '!'",
            [$prefix . SearchClause::escape($value['value']) . $suffix],
        );
    }

    /** @param array{kind: 'column', sql: string, raw: bool} $column */
    private function wrap(array $column): string
    {
        if ($column['raw']) {
            return $column['sql'];
        }
        $base = $this->builder instanceof EloquentBuilder ? $this->builder->getQuery() : $this->builder;

        return $base->getGrammar()->wrap($column['sql']);
    }

    // ── Refusals ─────────────────────────────────────────────────────────────

    protected static function unsupported(string $what): NotImplementedException
    {
        return new NotImplementedException('unsupported_filter', "\$filter: {$what} is not supported.");
    }

    protected static function invalid(string $why): BadRequestException
    {
        return new BadRequestException('invalid_filter', "\$filter: {$why}.");
    }

    private static function describe(FilterExpression $expr): string
    {
        return match (true) {
            $expr instanceof FunctionCallExpression => "the function {$expr->name}()",
            $expr instanceof BinaryExpression       => "the operator '" . strtolower($expr->operator->name) . "'",
            $expr instanceof UnaryExpression        => "the operator '" . strtolower($expr->operator->name) . "'",
            default                                 => 'this expression',
        };
    }
}
