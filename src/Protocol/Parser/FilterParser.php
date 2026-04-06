<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Parser;

use LaravelUi5\OData\Exception\BadRequestException;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryOperator;
use LaravelUi5\OData\Protocol\Planning\Expression\FilterExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\FunctionCallExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\LambdaExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\LambdaOperator;
use LaravelUi5\OData\Protocol\Planning\Expression\LambdaVariableExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\LiteralExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\NullLiteralExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\PropertyPathExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\UnaryExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\UnaryOperator;

/**
 * OData $filter expression parser — Shunting Yard producing FilterExpression directly.
 *
 * Eliminates the 83-class Node tree by placing FilterExpression objects on the
 * operand stack during parsing. Property names are stored as unresolved strings
 * in PropertyPathExpression segments; callers resolve them against an EntityType.
 *
 * Reuses the proven operator precedence levels and regex patterns from the legacy
 * parser but is fully self-contained with no legacy dependencies.
 */
final class FilterParser
{
    // ── Operator symbol registry ────────────────────────────────────────
    // Keyed by lowercase symbol → [precedence, isUnary, isBinary, isFunction, isLambda]

    private const OPERATORS = [
        // Functions (precedence 8, unary/function-call)
        'concat'              => [8, false, false, true, false],
        'contains'            => [8, false, false, true, false],
        'endswith'            => [8, false, false, true, false],
        'indexof'             => [8, false, false, true, false],
        'length'              => [8, false, false, true, false],
        'startswith'          => [8, false, false, true, false],
        'substring'           => [8, false, false, true, false],
        'matchespattern'      => [8, false, false, true, false],
        'tolower'             => [8, false, false, true, false],
        'toupper'             => [8, false, false, true, false],
        'trim'                => [8, false, false, true, false],
        'ceiling'             => [8, false, false, true, false],
        'floor'               => [8, false, false, true, false],
        'round'               => [8, false, false, true, false],
        'cast'                => [8, false, false, true, false],
        'date'                => [8, false, false, true, false],
        'day'                 => [8, false, false, true, false],
        'fractionalseconds'   => [8, false, false, true, false],
        'hour'                => [8, false, false, true, false],
        'maxdatetime'         => [8, false, false, true, false],
        'mindatetime'         => [8, false, false, true, false],
        'minute'              => [8, false, false, true, false],
        'month'               => [8, false, false, true, false],
        'now'                 => [8, false, false, true, false],
        'second'              => [8, false, false, true, false],
        'time'                => [8, false, false, true, false],
        'totaloffsetminutes'  => [8, false, false, true, false],
        'totalseconds'        => [8, false, false, true, false],
        'year'                => [8, false, false, true, false],
        // In operator (precedence 8, unary-style)
        'in'                  => [8, true, false, false, false],
        // Has operator (precedence 8, binary with hint)
        'has'                 => [8, false, true, false, false],
        // Not (precedence 7, unary)
        'not'                 => [7, true, false, false, false],
        // Multiplicative (precedence 6)
        'mul'                 => [6, false, true, false, false],
        'div'                 => [6, false, true, false, false],
        'divby'               => [6, false, true, false, false],
        'mod'                 => [6, false, true, false, false],
        // Additive (precedence 5)
        'add'                 => [5, false, true, false, false],
        'sub'                 => [5, false, true, false, false],
        // Relational (precedence 4)
        'gt'                  => [4, false, true, false, false],
        'ge'                  => [4, false, true, false, false],
        'lt'                  => [4, false, true, false, false],
        'le'                  => [4, false, true, false, false],
        // Equality (precedence 3)
        'eq'                  => [3, false, true, false, false],
        'ne'                  => [3, false, true, false, false],
        // Conditional AND (precedence 2)
        'and'                 => [2, false, true, false, false],
        // Conditional OR (precedence 1)
        'or'                  => [1, false, true, false, false],
        // Lambda (precedence 8, unary)
        'any'                 => [8, false, false, false, true],
        'all'                 => [8, false, false, false, true],
    ];

    private const BINARY_OP_MAP = [
        'eq' => BinaryOperator::Eq, 'ne' => BinaryOperator::Ne,
        'gt' => BinaryOperator::Gt, 'ge' => BinaryOperator::Ge,
        'lt' => BinaryOperator::Lt, 'le' => BinaryOperator::Le,
        'and' => BinaryOperator::And, 'or' => BinaryOperator::Or,
        'add' => BinaryOperator::Add, 'sub' => BinaryOperator::Sub,
        'mul' => BinaryOperator::Mul, 'div' => BinaryOperator::Div,
        'divby' => BinaryOperator::DivBy, 'mod' => BinaryOperator::Mod,
        'has' => BinaryOperator::Has, 'in' => BinaryOperator::In,
    ];

    private const LAMBDA_OP_MAP = [
        'any' => LambdaOperator::Any,
        'all' => LambdaOperator::All,
    ];

    /** Canonical OData function names (where they differ from lowercase). */
    private const CANONICAL_NAMES = [
        'matchespattern'     => 'matchesPattern',
        'startswith'         => 'startswith',
        'endswith'           => 'endswith',
        'indexof'            => 'indexof',
        'tolower'            => 'tolower',
        'toupper'            => 'toupper',
        'fractionalseconds'  => 'fractionalseconds',
        'maxdatetime'        => 'maxdatetime',
        'mindatetime'        => 'mindatetime',
        'totaloffsetminutes' => 'totaloffsetminutes',
        'totalseconds'       => 'totalseconds',
    ];

    /** @var FilterExpression[] Operand stack */
    private array $operands = [];

    /** @var list<array{symbol: string, prec: int, isUnary: bool, isBinary: bool, isFunc: bool, isLambda: bool, args: list<FilterExpression>, attachedOp: ?array, navProp: ?string, lambdaVar: ?string}> */
    private array $operators = [];

    /** @var list<array{type: string, value: mixed}> Token history for lambda variable lookback */
    private array $tokens = [];

    private ExpressionLexer $lexer;

    /**
     * Parse a filter expression string into a FilterExpression IR.
     *
     * Property names are stored as unresolved strings in PropertyPathExpression
     * segments. Callers (QueryPlanner) must resolve them against an EntityType.
     */
    public function parse(string $expression): FilterExpression
    {
        $this->lexer     = new ExpressionLexer($expression);
        $this->operands  = [];
        $this->operators = [];
        $this->tokens    = [];

        while (!$this->lexer->finished()) {
            if (!$this->findToken()) {
                throw new BadRequestException(
                    'parse_error',
                    'Unexpected token at: ' . $this->lexer->errorContext()
                );
            }
        }

        // Apply remaining operators
        while ($this->operators !== []) {
            $op = array_pop($this->operators);
            if ($op['symbol'] === '(') {
                throw new BadRequestException('parse_error', 'Unbalanced parentheses');
            }
            $this->applyOperator($op);
        }

        if (count($this->operands) !== 1) {
            // Single literal or empty expression
            if (count($this->operands) === 0) {
                throw new BadRequestException('parse_error', 'Empty expression');
            }
        }

        return $this->operands[0];
    }

    // ── Token dispatch (order matters!) ─────────────────────────────────

    private function findToken(): bool
    {
        return $this->tokenizeNull()
            || $this->tokenizeBoolean()
            || $this->tokenizeGuid()
            || $this->tokenizeDateTimeOffset()
            || $this->tokenizeDate()
            || $this->tokenizeTimeOfDay()
            || $this->tokenizeNumber()
            || $this->tokenizeSingleQuotedString()
            || $this->tokenizeDuration()
            || $this->tokenizeEnum()
            || $this->tokenizeLeftParen()
            || $this->tokenizeRightParen()
            || $this->tokenizeSeparator()
            || $this->tokenizeLambdaVariable()
            || $this->tokenizeLambdaProperty()
            || $this->tokenizeNavigationPropertyPath()
            || $this->tokenizeIdentifier()
            || $this->tokenizeOperator();
    }

    // ── Literal tokenizers ──────────────────────────────────────────────

    private function tokenizeNull(): bool
    {
        $token = $this->lexer->maybeLiteral('null');
        if ($token === null) {
            return false;
        }
        $this->pushOperand(new NullLiteralExpression());
        $this->tokens[] = ['type' => 'null', 'value' => null];
        return true;
    }

    private function tokenizeBoolean(): bool
    {
        $token = $this->lexer->with(fn() => $this->lexer->boolean());
        if ($token === null) {
            return false;
        }
        $this->pushOperand(new LiteralExpression($token === 'true', 'Edm.Boolean'));
        $this->tokens[] = ['type' => 'boolean', 'value' => $token === 'true'];
        return true;
    }

    private function tokenizeGuid(): bool
    {
        $token = $this->lexer->with(fn() => $this->lexer->guid());
        if ($token === null) {
            return false;
        }
        $this->pushOperand(new LiteralExpression($token, 'Edm.Guid'));
        $this->tokens[] = ['type' => 'guid', 'value' => $token];
        return true;
    }

    private function tokenizeDateTimeOffset(): bool
    {
        $token = $this->lexer->with(fn() => $this->lexer->dateTimeOffset());
        if ($token === null) {
            return false;
        }
        $this->pushOperand(new LiteralExpression($token, 'Edm.DateTimeOffset'));
        $this->tokens[] = ['type' => 'datetime', 'value' => $token];
        return true;
    }

    private function tokenizeDate(): bool
    {
        $token = $this->lexer->with(fn() => $this->lexer->date());
        if ($token === null) {
            return false;
        }
        $this->pushOperand(new LiteralExpression($token, 'Edm.Date'));
        $this->tokens[] = ['type' => 'date', 'value' => $token];
        return true;
    }

    private function tokenizeTimeOfDay(): bool
    {
        $token = $this->lexer->with(fn() => $this->lexer->timeOfDay());
        if ($token === null) {
            return false;
        }
        $this->pushOperand(new LiteralExpression($token, 'Edm.TimeOfDay'));
        $this->tokens[] = ['type' => 'time', 'value' => $token];
        return true;
    }

    private function tokenizeNumber(): bool
    {
        $value = $this->lexer->with(fn() => $this->lexer->number());
        if ($value === null) {
            return false;
        }
        $edmType = is_int($value) ? 'Edm.Int64' : 'Edm.Double';
        $this->pushOperand(new LiteralExpression($value, $edmType));
        $this->tokens[] = ['type' => 'number', 'value' => $value];
        return true;
    }

    private function tokenizeSingleQuotedString(): bool
    {
        $token = $this->lexer->with(fn() => $this->lexer->quotedString("'"));
        if ($token === null) {
            return false;
        }
        $this->pushOperand(new LiteralExpression($token, 'Edm.String'));
        $this->tokens[] = ['type' => 'string', 'value' => $token];
        return true;
    }

    private function tokenizeDuration(): bool
    {
        $token = $this->lexer->with(fn() => $this->lexer->duration());
        if ($token === null) {
            return false;
        }
        $this->pushOperand(new LiteralExpression($token, 'Edm.Duration'));
        $this->tokens[] = ['type' => 'duration', 'value' => $token];
        return true;
    }

    private function tokenizeEnum(): bool
    {
        $token = $this->lexer->with(function () {
            $name = $this->lexer->qualifiedIdentifier();
            $value = $this->lexer->quotedString("'");
            return $name . "'" . $value . "'";
        });
        if ($token === null) {
            return false;
        }
        $this->pushOperand(new LiteralExpression($token, 'Edm.Enum'));
        $this->tokens[] = ['type' => 'enum', 'value' => $token];
        return true;
    }

    // ── Parentheses and separators ──────────────────────────────────────

    private function tokenizeLeftParen(): bool
    {
        if ($this->lexer->maybeChar('(') === null) {
            return false;
        }

        // Create group entry on operator stack
        $group = [
            'symbol' => '(', 'prec' => 0,
            'isUnary' => false, 'isBinary' => false, 'isFunc' => false, 'isLambda' => false,
            'args' => [], 'attachedOp' => null, 'navProp' => null, 'lambdaVar' => null,
            'operandCount' => count($this->operands), // track operands at group open
        ];

        // If previous token was a function, in, or lambda: attach it to this group
        if ($this->operators !== []) {
            $last = end($this->operators);
            if ($last['isFunc'] || $last['isLambda'] || $last['symbol'] === 'in') {
                $group['attachedOp'] = array_pop($this->operators);
            }
        }

        $this->operators[] = $group;
        $this->tokens[] = ['type' => 'lparen', 'value' => '('];
        return true;
    }

    private function tokenizeRightParen(): bool
    {
        if ($this->lexer->maybeChar(')') === null) {
            return false;
        }

        // Pop and apply operators back to the matching group
        while ($this->operators !== []) {
            $top = end($this->operators);
            if ($top['symbol'] === '(') {
                break;
            }
            $this->applyOperator(array_pop($this->operators));
        }

        if ($this->operators === []) {
            throw new BadRequestException('parse_error', 'Unbalanced right parenthesis');
        }

        // Pop the group
        $group = array_pop($this->operators);
        $attached = $group['attachedOp'];

        // Check if operands were added inside this group
        $hasNewOperands = count($this->operands) > $group['operandCount'];

        if ($attached !== null) {
            if ($attached['isFunc']) {
                if ($hasNewOperands) {
                    $attached['args'][] = array_pop($this->operands);
                }
                $this->pushOperand(new FunctionCallExpression($attached['symbol'], $attached['args']));
            } elseif ($attached['isLambda']) {
                $bodyExpr = $hasNewOperands ? array_pop($this->operands) : new NullLiteralExpression();
                $lambdaOp = self::LAMBDA_OP_MAP[$attached['symbol']];
                $navProp = $attached['navProp'] ?? '';
                $variable = $attached['lambdaVar'] ?? '';

                $collection = new PropertyPathExpression([$navProp]);
                $this->pushOperand(new LambdaExpression($collection, $variable, $bodyExpr, $lambdaOp));
            } elseif ($attached['symbol'] === 'in') {
                if ($hasNewOperands) {
                    $attached['args'][] = array_pop($this->operands);
                }
                $left = array_pop($this->operands);
                $listExpr = new FunctionCallExpression('__list', $attached['args']);
                $this->pushOperand(new BinaryExpression($left, BinaryOperator::In, $listExpr));
            }
        }

        $this->tokens[] = ['type' => 'rparen', 'value' => ')'];
        return true;
    }

    private function tokenizeSeparator(): bool
    {
        $token = $this->lexer->with(fn() => $this->lexer->expression(',\s?'));
        if ($token === null) {
            return false;
        }

        // Pop operators back to the group, collecting arguments
        while ($this->operators !== []) {
            $top = end($this->operators);
            if ($top['symbol'] === '(') {
                break;
            }
            $this->applyOperator(array_pop($this->operators));
        }

        // Add current operand as argument to the attached function
        if ($this->operators !== []) {
            $groupIdx = array_key_last($this->operators);
            $group = &$this->operators[$groupIdx];
            if ($group['attachedOp'] !== null && $this->operands !== []) {
                $group['attachedOp']['args'][] = array_pop($this->operands);
            }
        }

        $this->tokens[] = ['type' => 'separator', 'value' => ','];
        return true;
    }

    // ── Lambda variable and property ────────────────────────────────────

    private function tokenizeLambdaVariable(): bool
    {
        $token = $this->lexer->with(fn() => $this->lexer->expression(ExpressionLexer::LAMBDA_VARIABLE));
        if ($token === null) {
            return false;
        }

        $varName = rtrim($token, ':');
        $this->pushOperand(new LambdaVariableExpression($varName));
        $this->tokens[] = ['type' => 'lambda_variable', 'value' => $varName];

        // Attach variable to the lambda operator on the operator stack
        foreach (array_reverse($this->operators) as $idx => $op) {
            if ($op['symbol'] === '(' && $op['attachedOp'] !== null && $op['attachedOp']['isLambda']) {
                $realIdx = count($this->operators) - 1 - $idx;
                $this->operators[$realIdx]['attachedOp']['lambdaVar'] = $varName;
                // Pop the lambda variable from operands (it's captured, not used directly)
                array_pop($this->operands);
                break;
            }
        }

        return true;
    }

    private function tokenizeLambdaProperty(): bool
    {
        // Find the most recent lambda variable
        $variable = null;
        foreach (array_reverse($this->tokens) as $tok) {
            if ($tok['type'] === 'lambda_variable') {
                $variable = $tok['value'];
                break;
            }
        }

        if ($variable === null) {
            return false;
        }

        $token = $this->lexer->with(function () use ($variable) {
            $this->lexer->literal($variable . '/');
            return $this->lexer->identifier();
        });

        if ($token === null) {
            return false;
        }

        $this->pushOperand(new PropertyPathExpression([$token]));
        $this->tokens[] = ['type' => 'lambda_property', 'value' => $token];
        return true;
    }

    // ── Navigation property path (identifier + '/') ────────────────────

    private function tokenizeNavigationPropertyPath(): bool
    {
        $token = $this->lexer->with(function () {
            $id = $this->lexer->identifier();
            $this->lexer->char('/');
            // Exclude operator keywords
            return isset(self::OPERATORS[strtolower($id)]) ? null : $id;
        });

        if ($token === null) {
            return false;
        }

        // Navigation property becomes part of the next operator (lambda).
        // Push as operand; lambda handling will pop it.
        $this->pushOperand(new PropertyPathExpression([$token]));
        $this->tokens[] = ['type' => 'navigation', 'value' => $token];
        return true;
    }

    // ── Identifier (property name) ──────────────────────────────────────

    private function tokenizeIdentifier(): bool
    {
        $token = $this->lexer->with(function () {
            $id = $this->lexer->identifier();
            $lower = strtolower($id);

            // Allow function names (like 'date', 'time', 'year', etc.) as property
            // names when NOT followed by '('. Functions always require parentheses;
            // bare identifiers are property paths.
            if (isset(self::OPERATORS[$lower])) {
                $def = self::OPERATORS[$lower];
                $isFunc = $def[3]; // isFunction flag

                if ($isFunc && !$this->lexer->peekChar('(')) {
                    // It's a function name used as a property — allow it
                    return $id;
                }

                // Binary/unary operators (eq, and, not, etc.) are never property names
                return null;
            }

            return $id;
        });

        if ($token === null) {
            return false;
        }

        $this->pushOperand(new PropertyPathExpression([$token]));
        $this->tokens[] = ['type' => 'property', 'value' => $token];
        return true;
    }

    // ── Operator tokenizer ──────────────────────────────────────────────

    private function tokenizeOperator(): bool
    {
        foreach (self::OPERATORS as $symbol => $def) {
            [$prec, $isUnary, $isBinary, $isFunc, $isLambda] = $def;

            $matched = null;

            if ($isFunc || $isLambda) {
                $matched = $this->lexer->func($symbol);
            } elseif ($isUnary && !$isBinary) {
                $matched = $this->lexer->unaryOperator($symbol);
            } else {
                $matched = $this->lexer->operator($symbol);
            }

            if ($matched === null) {
                continue;
            }

            $o1 = [
                'symbol' => self::CANONICAL_NAMES[$symbol] ?? $symbol,
                'prec' => $prec,
                'isUnary' => $isUnary,
                'isBinary' => $isBinary,
                'isFunc' => $isFunc,
                'isLambda' => $isLambda,
                'args' => [],
                'attachedOp' => null,
                'navProp' => null,
                'lambdaVar' => null,
            ];

            // For lambda operators: pop the navigation property from operands
            if ($isLambda && $this->operands !== []) {
                $navOperand = array_pop($this->operands);
                if ($navOperand instanceof PropertyPathExpression && $navOperand->segments !== []) {
                    $o1['navProp'] = $navOperand->segments[0];
                }
            }

            // Shunting Yard: pop higher-or-equal precedence operators
            while ($this->operators !== []) {
                $o2 = end($this->operators);
                if ($o2['symbol'] === '(') {
                    break;
                }
                if (!$o1['isUnary'] || ($o1['isUnary'] && $o2['isUnary'])) {
                    if ($o2['prec'] >= $o1['prec']) {
                        $this->applyOperator(array_pop($this->operators));
                        continue;
                    }
                }
                break;
            }

            $this->operators[] = $o1;
            $this->tokens[] = ['type' => 'operator', 'value' => $o1['symbol']];
            return true;
        }

        return false;
    }

    // ── Operator application ────────────────────────────────────────────

    private function applyOperator(array $op): void
    {
        $symbol = $op['symbol'];

        if ($op['isFunc']) {
            // Function: just push it (arguments handled via parens)
            $this->pushOperand(new FunctionCallExpression($symbol, $op['args']));
            return;
        }

        if ($op['isLambda']) {
            // Lambda without parentheses (shouldn't happen in valid OData, but handle gracefully)
            $lambdaOp = self::LAMBDA_OP_MAP[$symbol];
            $navProp = $op['navProp'] ?? '';
            $variable = $op['lambdaVar'] ?? '';
            $body = array_pop($this->operands) ?? new NullLiteralExpression();
            $collection = new PropertyPathExpression([$navProp]);
            $this->pushOperand(new LambdaExpression($collection, $variable, $body, $lambdaOp));
            return;
        }

        if ($op['isUnary'] && $symbol === 'not') {
            $operand = array_pop($this->operands);
            if ($operand === null) {
                throw new BadRequestException('parse_error', "Missing operand for 'not'");
            }
            $this->pushOperand(new UnaryExpression(UnaryOperator::Not, $operand));
            return;
        }

        if ($op['isUnary'] && $symbol === 'in') {
            // 'in' handled during paren closing (right paren collects the list)
            // If we get here, something went wrong
            return;
        }

        // Binary operator
        if (!isset(self::BINARY_OP_MAP[$symbol])) {
            throw new BadRequestException('parse_error', "Unknown operator: {$symbol}");
        }

        $right = array_pop($this->operands);
        $left  = array_pop($this->operands);

        if ($left === null || $right === null) {
            throw new BadRequestException('parse_error', "Missing operand for '{$symbol}'");
        }

        $this->pushOperand(new BinaryExpression($left, self::BINARY_OP_MAP[$symbol], $right));
    }

    private function pushOperand(FilterExpression $expr): void
    {
        $this->operands[] = $expr;
    }
}
