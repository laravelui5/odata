<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Protocol\Parser;

use LaravelUi5\OData\Exception\BadRequestException;

/**
 * OData expression lexer — tokenizes filter/search/compute expressions.
 *
 * Uses the same regex patterns as the legacy Expression\Lexer (OData ABNF spec)
 * but is self-contained with no legacy infrastructure dependencies.
 *
 * @link https://docs.oasis-open.org/odata/odata/v4.01/os/abnf/odata-abnf-construction-rules.txt
 */
final class ExpressionLexer
{
    // ── OData ABNF regex patterns ───────────────────────────────────────

    public const IDENTIFIER = '([A-Za-z_\p{L}\p{Nl}][A-Za-z_0-9\p{L}\p{Nl}\p{Nd}\p{Mn}\p{Mc}\p{Pc}\p{Cf}]{0,127})';
    public const QUALIFIED_IDENTIFIER = '(?:' . self::IDENTIFIER . '\.?)*' . self::IDENTIFIER;
    public const DURATION = '(-?)P(?=\d|T\d)(\d+Y)?(\d+M)?(\d+[DW])?(T(\d+H)?(\d+M)?((\d+(\.\d+)?)S)?)?';
    public const DATETIME_OFFSET = '[0-9]{4,}-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])T([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]([.][0-9]{1,12})?(Z|[+-][0-9][0-9]:[0-9][0-9])';
    public const DATE = '[0-9]{4,}-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])';
    public const GUID = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}';
    public const TIME_OF_DAY = '([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]([.][0-9]{1,12})?';
    public const DIGIT = '\d';
    public const LAMBDA_VARIABLE = self::IDENTIFIER . '\:';

    private int $pos = 0;
    private readonly int $len;

    public function __construct(
        private readonly string $text,
    ) {
        $this->len = strlen($text);
    }

    // ── Backtracking ────────────────────────────────────────────────────

    /**
     * Try a parse action; on failure (null return or exception), reset position.
     */
    public function with(callable $callback): mixed
    {
        $savedPos = $this->pos;

        try {
            $result = $callback($this);
        } catch (BadRequestException) {
            $result = null;
        }

        if ($result === null) {
            $this->pos = $savedPos;
        }

        return $result;
    }

    // ── Core matching ───────────────────────────────────────────────────

    public function finished(): bool
    {
        return $this->pos >= $this->len;
    }

    public function remaining(): string
    {
        return substr($this->text, $this->pos);
    }

    public function errorContext(): string
    {
        $ctx = 32;
        $left = max($this->pos - $ctx, 0);
        $right = min($left + $ctx * 2, $this->len);

        if ($this->pos >= $this->len) {
            return sprintf('%s<END', substr($this->text, $left, $this->pos - $left));
        }

        return sprintf(
            '%s>%s<%s',
            substr($this->text, $left, $this->pos - $left),
            $this->text[$this->pos],
            substr($this->text, $this->pos + 1, $right - $this->pos)
        );
    }

    /**
     * Match regex at current position, advance cursor, return match.
     *
     * @throws BadRequestException on no match
     */
    public function expression(string $pattern, bool $caseSensitive = true, int $group = 0): string
    {
        if ($this->pos > $this->len) {
            throw new BadRequestException('lexer_eof', "Expected {$pattern} but reached end of string");
        }

        $flags = $caseSensitive ? '' : 'i';
        $result = preg_match("@^{$pattern}@{$flags}", substr($this->text, $this->pos), $matches);

        if ($result !== 1) {
            throw new BadRequestException('lexer_no_match', "Expected {$pattern} at: " . $this->errorContext());
        }

        $match = $matches[$group];
        $this->pos += strlen($matches[0]); // advance by full match, not group
        return $match;
    }

    public function maybeExpression(string $pattern, bool $caseSensitive = true, int $group = 0): ?string
    {
        return $this->with(fn() => $this->expression($pattern, $caseSensitive, $group));
    }

    /**
     * Match a single character (or any character if $char is empty).
     *
     * @throws BadRequestException
     */
    public function char(string $char = ''): string
    {
        if ($this->pos >= $this->len) {
            throw new BadRequestException('lexer_eof', "Expected '{$char}' but reached end of string");
        }

        $next = $this->text[$this->pos];

        if ($char === '' || $next === $char) {
            $this->pos++;
            return $next;
        }

        throw new BadRequestException('lexer_char', "Expected '{$char}' but got '{$next}' at: " . $this->errorContext());
    }

    public function maybeChar(string $char): ?string
    {
        return $this->with(fn() => $this->char($char));
    }

    /**
     * Check if the next non-whitespace character matches, without consuming it.
     */
    public function peekChar(string $char): bool
    {
        $pos = $this->pos;

        // Skip whitespace
        while ($pos < $this->len && $this->text[$pos] === ' ') {
            $pos++;
        }

        return $pos < $this->len && $this->text[$pos] === $char;
    }

    /**
     * Match one of the given literal keywords at the current position.
     *
     * @throws BadRequestException
     */
    public function literal(string ...$keywords): string
    {
        foreach ($keywords as $kw) {
            if (substr($this->text, $this->pos, strlen($kw)) === $kw) {
                $this->pos += strlen($kw);
                return $kw;
            }
        }

        throw new BadRequestException('lexer_literal', 'Expected ' . implode('|', $keywords) . ' at: ' . $this->errorContext());
    }

    public function maybeLiteral(string ...$keywords): ?string
    {
        return $this->with(fn() => $this->literal(...$keywords));
    }

    // ── Whitespace ──────────────────────────────────────────────────────

    public function whitespace(): string
    {
        return $this->expression('\s+');
    }

    public function maybeWhitespace(): ?string
    {
        return $this->with(fn() => $this->whitespace());
    }

    // ── OData-specific token matchers ───────────────────────────────────

    public function identifier(): string
    {
        return $this->expression(self::IDENTIFIER);
    }

    public function qualifiedIdentifier(): string
    {
        return $this->expression(self::QUALIFIED_IDENTIFIER);
    }

    public function boolean(): string
    {
        return $this->literal('true', 'false');
    }

    public function guid(): string
    {
        return $this->expression(self::GUID);
    }

    public function dateTimeOffset(): string
    {
        return $this->expression(self::DATETIME_OFFSET);
    }

    public function date(): string
    {
        return $this->expression(self::DATE);
    }

    public function timeOfDay(): string
    {
        return $this->expression(self::TIME_OF_DAY);
    }

    public function duration(): string
    {
        return $this->expression(self::DURATION);
    }

    /**
     * Match a number (integer or float). Returns int|float|null on no match.
     */
    public function number(): int|float|null
    {
        return $this->with(function () {
            // NaN
            if ($this->maybeLiteral('NaN') !== null) {
                return NAN;
            }

            $sign = $this->maybeLiteral('+', '-');

            // INF / -INF
            if ($this->maybeLiteral('INF') !== null) {
                return $sign === '-' ? -INF : INF;
            }

            $chars = [];
            if ($sign !== null) {
                $chars[] = $sign;
            }

            $chars[] = $this->expression(self::DIGIT);

            while (($d = $this->maybeExpression(self::DIGIT)) !== null) {
                $chars[] = $d;
            }

            if ($this->maybeChar('.') !== null) {
                $chars[] = '.';
                $chars[] = $this->expression(self::DIGIT);
                while (($d = $this->maybeExpression(self::DIGIT)) !== null) {
                    $chars[] = $d;
                }
                return (float) implode('', $chars);
            }

            return (int) implode('', $chars);
        });
    }

    /**
     * Match a single-quoted string with '' escape handling.
     */
    public function quotedString(string $quote = "'"): string
    {
        $this->char($quote);
        $chars = [];

        while (true) {
            $ch = $this->char();
            if ($ch === $quote) {
                // Escaped quote: ''
                if ($this->pos < $this->len && $this->text[$this->pos] === $quote) {
                    $this->pos++;
                    $chars[] = $quote;
                    continue;
                }
                break;
            }
            $chars[] = $ch;
        }

        return implode('', $chars);
    }

    /**
     * Match an operator: \s{symbol}\s (case-insensitive).
     */
    public function operator(string $symbol): ?string
    {
        return $this->with(function () use ($symbol) {
            return trim($this->expression('\s' . $symbol . '\s', false), ' ');
        });
    }

    /**
     * Match a unary operator: {symbol}\s (case-insensitive).
     */
    public function unaryOperator(string $symbol): ?string
    {
        return $this->with(function () use ($symbol) {
            return trim($this->expression($symbol . '\s', false), ' ');
        });
    }

    /**
     * Match a function call: {symbol}( (case-insensitive). Backtracks the '('.
     */
    public function func(string $symbol): ?string
    {
        return $this->with(function () use ($symbol) {
            $result = $this->expression($symbol . '\(', false);
            if ($result) {
                $this->pos--; // backtrack the '(' so caller can handle it
                return trim($result, '(');
            }
            return null;
        });
    }

    /**
     * Match balanced parentheses, return inner content.
     */
    public function matchingParenthesis(): string
    {
        $this->char('(');
        $chars = [];
        $nesting = 0;

        while (true) {
            $ch = $this->char();
            if ($ch === '(') {
                $nesting++;
            }
            if ($ch === ')') {
                if ($nesting === 0) {
                    break;
                }
                $nesting--;
            }
            $chars[] = $ch;
        }

        return implode('', $chars);
    }

    public function maybeMatchingParenthesis(): ?string
    {
        return $this->with(fn() => $this->matchingParenthesis());
    }
}
