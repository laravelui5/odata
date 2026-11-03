<?php

declare(strict_types=1);

use LaravelUi5\OData\Protocol\Parser\FilterParser;
use LaravelUi5\OData\Protocol\Parser\UnresolvedLambdaExpression;
use LaravelUi5\OData\Protocol\Parser\UnresolvedPathExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\FilterExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\FilterExpressionVisitor;
use LaravelUi5\OData\Protocol\Planning\Expression\FunctionCallExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\LambdaExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\LambdaVariableExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\LiteralExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\NullLiteralExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\PropertyPathExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\UnaryExpression;

use function LaravelUi5\OData\Tests\narrow;
use function LaravelUi5\OData\Tests\Protocol\Parser\translateFilter;

/*
 * The parser's tree says it is unresolved, the resolver's says it is not (OP45): paths and lambdas leave the parser as
 * names, and only PropertyResolver turns them into properties — so every PropertyPathExpression a driver or a custom
 * resolver reads holds properties, as its type says.
 */

/** Visits a whole tree; accept() throws on a node no visitor knows, an unresolved one. */
function visitAll(FilterExpression $tree): int
{
    return $tree->accept(new class implements FilterExpressionVisitor {
        public function visitLiteral(LiteralExpression $node): int { return 1; }
        public function visitNullLiteral(NullLiteralExpression $node): int { return 1; }
        public function visitPropertyPath(PropertyPathExpression $node): int { return 1; }
        public function visitBinary(BinaryExpression $node): int { return 1 + $node->left->accept($this) + $node->right->accept($this); }
        public function visitUnary(UnaryExpression $node): int { return 1 + $node->operand->accept($this); }
        public function visitFunctionCall(FunctionCallExpression $node): int
        {
            return 1 + array_sum(array_map(fn(FilterExpression $a) => $a->accept($this), $node->arguments));
        }
        public function visitLambda(LambdaExpression $node): int { return 1 + $node->collection->accept($this) + $node->predicate->accept($this); }
        public function visitLambdaVariable(LambdaVariableExpression $node): int { return 1; }
    });
}

it('leaves the parser with names, not properties', function () {
    $binary = narrow(BinaryExpression::class, (new FilterParser())->parse("origin eq 'lhr'"));

    expect(narrow(UnresolvedPathExpression::class, $binary->left)->names)->toBe(['origin']);
});

it('leaves the parser with the lambda navigation as a name', function () {
    $lambda = narrow(UnresolvedLambdaExpression::class, (new FilterParser())->parse("airports/any(d:d/name eq 'x')"));

    expect($lambda->navigation)->toBe('airports')
        ->and($lambda->variable)->toBe('d');
});

it('refuses to be visited while unresolved', function () {
    visitAll((new FilterParser())->parse("origin eq 'lhr'"));
})->throws(LogicException::class, 'No visitor method for');

it('resolves every path and lambda, so the whole tree can be visited', function (string $filter) {
    expect(visitAll(translateFilter($filter)))->toBeGreaterThan(0);
})->with([
    'comparison'          => ["origin eq 'lhr'"],
    'logical and unary'   => ["not (origin eq 'lhr') and id gt 2"],
    'function arguments'  => ["contains(origin, 'h') or startswith(destination, 'l')"],
    'lambda with a path'  => ["airports/any(d:d/name eq 'x')"],
    'lambda inside logic' => ["origin eq 'lhr' and airports/all(d:d/code ne 'y')"],
]);

it('resolves the lambda collection to the navigation property', function () {
    $lambda = narrow(LambdaExpression::class, translateFilter("airports/any(d:d/name eq 'x')"));

    expect($lambda->collection->segments[0]->getName())->toBe('airports');
});
