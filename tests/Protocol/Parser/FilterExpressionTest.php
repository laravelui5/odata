<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\BinaryOperator;
use LaravelUi5\OData\Protocol\Planning\Expression\FunctionCallExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\LiteralExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\NullLiteralExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\PropertyPathExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\UnaryExpression;
use LaravelUi5\OData\Protocol\Planning\Expression\UnaryOperator;

use function LaravelUi5\OData\Tests\Protocol\Parser\translateFilter;

use function LaravelUi5\OData\Tests\narrow;

/**
 * Build an entity type with typed properties for literal-type tests.
 * All properties typed to their "natural" Edm type so the type hint doesn't mask the literal.
 */
function typedEntityType(): EntityType
{
    $id       = new Property('id', new PrimitiveType(EdmPrimitiveType::Int32));
    $origin   = new Property('origin', new PrimitiveType(EdmPrimitiveType::String));
    $dateCol  = new Property('created', new PrimitiveType(EdmPrimitiveType::Date));
    $dtCol    = new Property('modified', new PrimitiveType(EdmPrimitiveType::DateTimeOffset));
    $timeCol  = new Property('startTime', new PrimitiveType(EdmPrimitiveType::TimeOfDay));
    $guidCol  = new Property('uid', new PrimitiveType(EdmPrimitiveType::Guid));
    $durCol   = new Property('elapsed', new PrimitiveType(EdmPrimitiveType::Duration));
    $boolCol  = new Property('active', new PrimitiveType(EdmPrimitiveType::Boolean));
    $dblCol   = new Property('score', new PrimitiveType(EdmPrimitiveType::Double));

    return new EntityType(
        namespace: 'Test',
        name: 'Record',
        key: [$id],
        declaredProperties: [$id, $origin, $dateCol, $dtCol, $timeCol, $guidCol, $durCol, $boolCol, $dblCol],
    );
}

// ── String comparisons ────────────────────────────────────────────────────

it('translates string equality with single quotes', function () {
    $expr = translateFilter("origin eq 'test'");
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::Eq);
    $left = narrow(PropertyPathExpression::class, $expr->left);
    $right = narrow(LiteralExpression::class, $expr->right);
    expect($right->value)->toBe('test');
    expect($right->edmType)->toBe('Edm.String');
});

// ── Integer comparisons ─────────────────────────────────────────────────

it('translates integer equality', function () {
    $expr = translateFilter('id eq 4');
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::Eq);
    $right = narrow(LiteralExpression::class, $expr->right);
    expect($right->value)->toBe(4);
    expect($right->edmType)->toBe('Edm.Int32');
});

it('translates integer greater-than', function () {
    $expr = translateFilter('id gt 4');
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::Gt);
});

it('translates integer less-than', function () {
    $expr = translateFilter('id lt 4');
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::Lt);
});

it('translates integer greater-than-or-equal', function () {
    $expr = translateFilter('id ge 4');
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::Ge);
});

it('translates integer less-than-or-equal', function () {
    $expr = translateFilter('id le 4');
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::Le);
});

it('translates integer not-equal', function () {
    $expr = translateFilter('id ne 4');
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::Ne);
});

// ── Property-to-property comparison ─────────────────────────────────────

it('translates property eq property', function () {
    $expr = translateFilter('id eq origin');
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(PropertyPathExpression::class, $expr->left);
    $right = narrow(PropertyPathExpression::class, $expr->right);
});

// ── Logical connectives ─────────────────────────────────────────────────

it('translates and condition', function () {
    $expr = translateFilter('id lt 4 and id gt 2');
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::And);
    $left = narrow(BinaryExpression::class, $expr->left);
    expect($left->operator)->toBe(BinaryOperator::Lt);
    $right = narrow(BinaryExpression::class, $expr->right);
    expect($right->operator)->toBe(BinaryOperator::Gt);
});

it('translates or condition', function () {
    $expr = translateFilter('id lt 4 or id gt 2');
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::Or);
});

it('translates chained or conditions', function () {
    $expr = translateFilter('id lt 4 or id lt 3 or id lt 2');
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::Or);
});

it('translates mixed or/and with precedence', function () {
    // "and" binds tighter than "or": id lt 4 or (id lt 3 and id lt 2)
    $expr = translateFilter('id lt 4 or id lt 3 and id lt 2');
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::Or);
    $right = narrow(BinaryExpression::class, $expr->right);
    expect($right->operator)->toBe(BinaryOperator::And);
});

it('translates parenthesized conditions', function () {
    $expr = translateFilter('(id lt 4 and id ge 7) or id gt 3');
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::Or);
    $left = narrow(BinaryExpression::class, $expr->left);
    expect($left->operator)->toBe(BinaryOperator::And);
});

it('translates two grouped and conditions with or', function () {
    $expr = translateFilter('(id lt 4 and id ge 7) or (id gt 3 and id gt 2)');
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::Or);
    $left = narrow(BinaryExpression::class, $expr->left);
    expect($left->operator)->toBe(BinaryOperator::And);
    $right = narrow(BinaryExpression::class, $expr->right);
    expect($right->operator)->toBe(BinaryOperator::And);
});

// ── Not operator ────────────────────────────────────────────────────────

it('translates not equality', function () {
    $expr = translateFilter("not (origin eq 'a')");
    $expr = narrow(UnaryExpression::class, $expr);
    expect($expr->operator)->toBe(UnaryOperator::Not);
    $operand = narrow(BinaryExpression::class, $expr->operand);
});

it('translates not-contains with and', function () {
    $expr = translateFilter("not (contains(origin,'a')) and ((origin eq 'abcd') or (origin eq 'e'))");
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::And);
    $left = narrow(UnaryExpression::class, $expr->left);
});

it('translates and with negated equality', function () {
    $expr = translateFilter("origin eq 'b' and not (origin eq 'a')");
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::And);
    $right = narrow(UnaryExpression::class, $expr->right);
});

// ── Arithmetic operators ────────────────────────────────────────────────

it('translates add', function () {
    $expr = translateFilter('id add 3.14 eq 1.59');
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::Eq);
    $left = narrow(BinaryExpression::class, $expr->left);
    expect($left->operator)->toBe(BinaryOperator::Add);
});

it('translates sub', function () {
    $expr = translateFilter('origin eq 4 sub 3');
    $expr = narrow(BinaryExpression::class, $expr);
    $right = narrow(BinaryExpression::class, $expr->right);
    expect($right->operator)->toBe(BinaryOperator::Sub);
});

it('translates mul', function () {
    $expr = translateFilter('origin eq 4 mul 3');
    $expr = narrow(BinaryExpression::class, $expr);
    $right = narrow(BinaryExpression::class, $expr->right);
    expect($right->operator)->toBe(BinaryOperator::Mul);
});

it('translates div', function () {
    $expr = translateFilter('origin eq 4 div 3');
    $expr = narrow(BinaryExpression::class, $expr);
    $right = narrow(BinaryExpression::class, $expr->right);
    expect($right->operator)->toBe(BinaryOperator::Div);
});

it('translates divby', function () {
    $expr = translateFilter('origin eq 4 divby 3');
    $expr = narrow(BinaryExpression::class, $expr);
    $right = narrow(BinaryExpression::class, $expr->right);
    expect($right->operator)->toBe(BinaryOperator::DivBy);
});

it('translates mod', function () {
    $expr = translateFilter('origin eq 4 mod 3');
    $expr = narrow(BinaryExpression::class, $expr);
    $right = narrow(BinaryExpression::class, $expr->right);
    expect($right->operator)->toBe(BinaryOperator::Mod);
});

// ── Negative literals ───────────────────────────────────────────────────

it('translates negative float literal', function () {
    $expr = translateFilter('-2.40');
    $expr = narrow(LiteralExpression::class, $expr);
    expect($expr->value)->toBe(-2.4);
});

// ── Null ────────────────────────────────────────────────────────────────

it('translates property eq null', function () {
    $expr = translateFilter('origin eq null');
    $expr = narrow(BinaryExpression::class, $expr);
    $right = narrow(NullLiteralExpression::class, $expr->right);
});

// ── Boolean literals ────────────────────────────────────────────────────

it('translates boolean eq true', function () {
    $type = typedEntityType();
    $expr = translateFilter('active eq true', $type);
    $expr = narrow(BinaryExpression::class, $expr);
    $right = narrow(LiteralExpression::class, $expr->right);
    expect($right->value)->toBe(true);
    expect($right->edmType)->toBe('Edm.Boolean');
});

it('translates boolean eq false', function () {
    $type = typedEntityType();
    $expr = translateFilter('active eq false', $type);
    $expr = narrow(BinaryExpression::class, $expr);
    $right = narrow(LiteralExpression::class, $expr->right);
    expect($right->value)->toBe(false);
    expect($right->edmType)->toBe('Edm.Boolean');
});

// ── Date/time literals (using typed properties to verify literal types) ──

it('translates date literal with typed property', function () {
    $type = typedEntityType();
    $expr = translateFilter('created eq 2000-01-01', $type);
    $expr = narrow(BinaryExpression::class, $expr);
    $right = narrow(LiteralExpression::class, $expr->right);
    expect($right->edmType)->toBe('Edm.Date');
});

it('translates datetime literal with typed property', function () {
    $type = typedEntityType();
    $expr = translateFilter('modified eq 2000-01-01T12:34:59Z', $type);
    $expr = narrow(BinaryExpression::class, $expr);
    $right = narrow(LiteralExpression::class, $expr->right);
    expect($right->edmType)->toBe('Edm.DateTimeOffset');
});

it('translates time literal with typed property', function () {
    $type = typedEntityType();
    $expr = translateFilter('startTime eq 04:11:12', $type);
    $expr = narrow(BinaryExpression::class, $expr);
    $right = narrow(LiteralExpression::class, $expr->right);
    expect($right->edmType)->toBe('Edm.TimeOfDay');
});

it('translates GUID literal with typed property', function () {
    $type = typedEntityType();
    $expr = translateFilter('uid eq 4AA33245-E2D1-470D-9433-01AAFCF0C83F', $type);
    $expr = narrow(BinaryExpression::class, $expr);
    $right = narrow(LiteralExpression::class, $expr->right);
    expect($right->edmType)->toBe('Edm.Guid');
});

it('translates duration literal with typed property', function () {
    $type = typedEntityType();
    $expr = translateFilter('elapsed eq PT1M', $type);
    $expr = narrow(BinaryExpression::class, $expr);
    $right = narrow(LiteralExpression::class, $expr->right);
    expect($right->edmType)->toBe('Edm.Duration');
});

it('translates duration PT36H with typed property', function () {
    $type = typedEntityType();
    $expr = translateFilter('elapsed eq PT36H', $type);
    $expr = narrow(BinaryExpression::class, $expr);
    $right = narrow(LiteralExpression::class, $expr->right);
    expect($right->edmType)->toBe('Edm.Duration');
});

it('translates complex duration P10DT2H30M with typed property', function () {
    $type = typedEntityType();
    $expr = translateFilter('elapsed eq P10DT2H30M', $type);
    $expr = narrow(BinaryExpression::class, $expr);
    $right = narrow(LiteralExpression::class, $expr->right);
    expect($right->edmType)->toBe('Edm.Duration');
});

it('translates datetime with positive offset', function () {
    $type = typedEntityType();
    $expr = translateFilter('modified eq 2000-01-01T12:34:59+01:00', $type);
    $expr = narrow(BinaryExpression::class, $expr);
    $right = narrow(LiteralExpression::class, $expr->right);
    expect($right->edmType)->toBe('Edm.DateTimeOffset');
});

it('translates datetime with negative offset', function () {
    $type = typedEntityType();
    $expr = translateFilter('modified eq 2000-01-01T12:34:59-01:00', $type);
    $expr = narrow(BinaryExpression::class, $expr);
    $right = narrow(LiteralExpression::class, $expr->right);
    expect($right->edmType)->toBe('Edm.DateTimeOffset');
});

// ── Type hinting — literal type inferred from property ──────────────────

it('hints right-side literal type from left-side Int32 property', function () {
    $expr = translateFilter('id eq 4');
    $expr = narrow(BinaryExpression::class, $expr);
    $right = narrow(LiteralExpression::class, $expr->right);
    expect($right->edmType)->toBe('Edm.Int32');
});

it('hints right-side literal type from left-side String property', function () {
    $expr = translateFilter("origin eq 'test'");
    $expr = narrow(BinaryExpression::class, $expr);
    $right = narrow(LiteralExpression::class, $expr->right);
    expect($right->edmType)->toBe('Edm.String');
});

it('string property hint overrides literal default type', function () {
    // When origin (String) is on the left, the right-side date literal gets hinted as String
    $expr = translateFilter('origin eq 2000-01-01');
    $expr = narrow(BinaryExpression::class, $expr);
    $right = narrow(LiteralExpression::class, $expr->right);
    expect($right->edmType)->toBe('Edm.String');
});

it('double property hint applies to integer literal', function () {
    $type = typedEntityType();
    $expr = translateFilter('score eq 4', $type);
    $expr = narrow(BinaryExpression::class, $expr);
    $right = narrow(LiteralExpression::class, $expr->right);
    expect($right->edmType)->toBe('Edm.Double');
});

// ── Case-insensitive operators ──────────────────────────────────────────

it('translates case-insensitive operator EQ', function () {
    $expr = translateFilter("origin EQ 'lax'");
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::Eq);
});

// ── Complex combined expressions ────────────────────────────────────────

it('translates and of two function comparisons', function () {
    $expr = translateFilter("endswith(origin,'Veniam et') eq true and startswith(origin,'Veniam et') eq true");
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::And);
    $left3 = narrow(BinaryExpression::class, $expr->left);
    $left = narrow(FunctionCallExpression::class, $left3->left);
    $right = narrow(BinaryExpression::class, $expr->right);
    $left2 = narrow(FunctionCallExpression::class, $right->left);
});

it('translates startswith eq true', function () {
    $expr = translateFilter("startswith(origin,'Veniam et') eq true");
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(FunctionCallExpression::class, $expr->left);
    expect($left->name)->toBe('startswith');
    $right = narrow(LiteralExpression::class, $expr->right);
    expect($right->value)->toBe(true);
});

it('translates boolean eq startswith reversed', function () {
    $expr = translateFilter("true eq startswith(origin,'Veniam et')");
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(LiteralExpression::class, $expr->left);
    $right = narrow(FunctionCallExpression::class, $expr->right);
});

it('translates property gt datetime arithmetic', function () {
    $expr = translateFilter('origin gt (now() sub PT3M)');
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::Gt);
    $right = narrow(BinaryExpression::class, $expr->right);
    expect($right->operator)->toBe(BinaryOperator::Sub);
});

it('translates nested function calls with cast', function () {
    $expr = translateFilter("(contains(tolower(cast(origin, 'Edm.String')),'alpha')) or (contains(tolower(cast(origin, 'Edm.String')),'alpha'))");
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::Or);
});

it('translates and with negated function comparison', function () {
    $expr = translateFilter("endswith(origin,'Veniam et') eq true and not (startswith(origin,'Veniam et') eq true)");
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::And);
    $right = narrow(UnaryExpression::class, $expr->right);
});

it('translates or with negated equality', function () {
    $expr = translateFilter("origin eq 'b' or not (origin eq 'a')");
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::Or);
    $right = narrow(UnaryExpression::class, $expr->right);
});

it('translates float in arithmetic expression', function () {
    $expr = translateFilter('id add 3.14 eq 1.59');
    $expr = narrow(BinaryExpression::class, $expr);
    $left = narrow(BinaryExpression::class, $expr->left);
    $right = narrow(LiteralExpression::class, $left->right);
    expect($right->value)->toBe(3.14);
});

it('translates chained add', function () {
    $expr = translateFilter('id add 3 add 5 eq 9');
    $expr = narrow(BinaryExpression::class, $expr);
    expect($expr->operator)->toBe(BinaryOperator::Eq);
    $left = narrow(BinaryExpression::class, $expr->left);
    expect($left->operator)->toBe(BinaryOperator::Add);
});
