<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Contracts\Container\PrimitiveTypeEnum;
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

/**
 * Build an entity type with typed properties for literal-type tests.
 * All properties typed to their "natural" Edm type so the type hint doesn't mask the literal.
 */
function typedEntityType(): EntityType
{
    $id       = new Property('id', new PrimitiveType(PrimitiveTypeEnum::Int32));
    $origin   = new Property('origin', new PrimitiveType(PrimitiveTypeEnum::String));
    $dateCol  = new Property('created', new PrimitiveType(PrimitiveTypeEnum::Date));
    $dtCol    = new Property('modified', new PrimitiveType(PrimitiveTypeEnum::DateTimeOffset));
    $timeCol  = new Property('startTime', new PrimitiveType(PrimitiveTypeEnum::TimeOfDay));
    $guidCol  = new Property('uid', new PrimitiveType(PrimitiveTypeEnum::Guid));
    $durCol   = new Property('elapsed', new PrimitiveType(PrimitiveTypeEnum::Duration));
    $boolCol  = new Property('active', new PrimitiveType(PrimitiveTypeEnum::Boolean));
    $dblCol   = new Property('score', new PrimitiveType(PrimitiveTypeEnum::Double));

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
    expect($expr)->toBeInstanceOf(BinaryExpression::class);
    expect($expr->operator)->toBe(BinaryOperator::Eq);
    expect($expr->left)->toBeInstanceOf(PropertyPathExpression::class);
    expect($expr->right)->toBeInstanceOf(LiteralExpression::class);
    expect($expr->right->value)->toBe('test');
    expect($expr->right->edmType)->toBe('Edm.String');
});

// ── Integer comparisons ─────────────────────────────────────────────────

it('translates integer equality', function () {
    $expr = translateFilter('id eq 4');
    expect($expr)->toBeInstanceOf(BinaryExpression::class);
    expect($expr->operator)->toBe(BinaryOperator::Eq);
    expect($expr->right)->toBeInstanceOf(LiteralExpression::class);
    expect($expr->right->value)->toBe(4);
    expect($expr->right->edmType)->toBe('Edm.Int32');
});

it('translates integer greater-than', function () {
    $expr = translateFilter('id gt 4');
    expect($expr->operator)->toBe(BinaryOperator::Gt);
});

it('translates integer less-than', function () {
    $expr = translateFilter('id lt 4');
    expect($expr->operator)->toBe(BinaryOperator::Lt);
});

it('translates integer greater-than-or-equal', function () {
    $expr = translateFilter('id ge 4');
    expect($expr->operator)->toBe(BinaryOperator::Ge);
});

it('translates integer less-than-or-equal', function () {
    $expr = translateFilter('id le 4');
    expect($expr->operator)->toBe(BinaryOperator::Le);
});

it('translates integer not-equal', function () {
    $expr = translateFilter('id ne 4');
    expect($expr->operator)->toBe(BinaryOperator::Ne);
});

// ── Property-to-property comparison ─────────────────────────────────────

it('translates property eq property', function () {
    $expr = translateFilter('id eq origin');
    expect($expr->left)->toBeInstanceOf(PropertyPathExpression::class);
    expect($expr->right)->toBeInstanceOf(PropertyPathExpression::class);
});

// ── Logical connectives ─────────────────────────────────────────────────

it('translates and condition', function () {
    $expr = translateFilter('id lt 4 and id gt 2');
    expect($expr)->toBeInstanceOf(BinaryExpression::class);
    expect($expr->operator)->toBe(BinaryOperator::And);
    expect($expr->left)->toBeInstanceOf(BinaryExpression::class);
    expect($expr->left->operator)->toBe(BinaryOperator::Lt);
    expect($expr->right)->toBeInstanceOf(BinaryExpression::class);
    expect($expr->right->operator)->toBe(BinaryOperator::Gt);
});

it('translates or condition', function () {
    $expr = translateFilter('id lt 4 or id gt 2');
    expect($expr->operator)->toBe(BinaryOperator::Or);
});

it('translates chained or conditions', function () {
    $expr = translateFilter('id lt 4 or id lt 3 or id lt 2');
    expect($expr)->toBeInstanceOf(BinaryExpression::class);
    expect($expr->operator)->toBe(BinaryOperator::Or);
});

it('translates mixed or/and with precedence', function () {
    // "and" binds tighter than "or": id lt 4 or (id lt 3 and id lt 2)
    $expr = translateFilter('id lt 4 or id lt 3 and id lt 2');
    expect($expr->operator)->toBe(BinaryOperator::Or);
    expect($expr->right)->toBeInstanceOf(BinaryExpression::class);
    expect($expr->right->operator)->toBe(BinaryOperator::And);
});

it('translates parenthesized conditions', function () {
    $expr = translateFilter('(id lt 4 and id ge 7) or id gt 3');
    expect($expr->operator)->toBe(BinaryOperator::Or);
    expect($expr->left->operator)->toBe(BinaryOperator::And);
});

it('translates two grouped and conditions with or', function () {
    $expr = translateFilter('(id lt 4 and id ge 7) or (id gt 3 and id gt 2)');
    expect($expr->operator)->toBe(BinaryOperator::Or);
    expect($expr->left->operator)->toBe(BinaryOperator::And);
    expect($expr->right->operator)->toBe(BinaryOperator::And);
});

// ── Not operator ────────────────────────────────────────────────────────

it('translates not equality', function () {
    $expr = translateFilter("not (origin eq 'a')");
    expect($expr)->toBeInstanceOf(UnaryExpression::class);
    expect($expr->operator)->toBe(UnaryOperator::Not);
    expect($expr->operand)->toBeInstanceOf(BinaryExpression::class);
});

it('translates not-contains with and', function () {
    $expr = translateFilter("not (contains(origin,'a')) and ((origin eq 'abcd') or (origin eq 'e'))");
    expect($expr)->toBeInstanceOf(BinaryExpression::class);
    expect($expr->operator)->toBe(BinaryOperator::And);
    expect($expr->left)->toBeInstanceOf(UnaryExpression::class);
});

it('translates and with negated equality', function () {
    $expr = translateFilter("origin eq 'b' and not (origin eq 'a')");
    expect($expr->operator)->toBe(BinaryOperator::And);
    expect($expr->right)->toBeInstanceOf(UnaryExpression::class);
});

// ── Arithmetic operators ────────────────────────────────────────────────

it('translates add', function () {
    $expr = translateFilter('id add 3.14 eq 1.59');
    expect($expr)->toBeInstanceOf(BinaryExpression::class);
    expect($expr->operator)->toBe(BinaryOperator::Eq);
    expect($expr->left)->toBeInstanceOf(BinaryExpression::class);
    expect($expr->left->operator)->toBe(BinaryOperator::Add);
});

it('translates sub', function () {
    $expr = translateFilter('origin eq 4 sub 3');
    expect($expr->right)->toBeInstanceOf(BinaryExpression::class);
    expect($expr->right->operator)->toBe(BinaryOperator::Sub);
});

it('translates mul', function () {
    $expr = translateFilter('origin eq 4 mul 3');
    expect($expr->right->operator)->toBe(BinaryOperator::Mul);
});

it('translates div', function () {
    $expr = translateFilter('origin eq 4 div 3');
    expect($expr->right->operator)->toBe(BinaryOperator::Div);
});

it('translates divby', function () {
    $expr = translateFilter('origin eq 4 divby 3');
    expect($expr->right->operator)->toBe(BinaryOperator::DivBy);
});

it('translates mod', function () {
    $expr = translateFilter('origin eq 4 mod 3');
    expect($expr->right->operator)->toBe(BinaryOperator::Mod);
});

// ── Negative literals ───────────────────────────────────────────────────

it('translates negative float literal', function () {
    $expr = translateFilter('-2.40');
    expect($expr)->toBeInstanceOf(LiteralExpression::class);
    expect($expr->value)->toBe(-2.4);
});

// ── Null ────────────────────────────────────────────────────────────────

it('translates property eq null', function () {
    $expr = translateFilter('origin eq null');
    expect($expr->right)->toBeInstanceOf(NullLiteralExpression::class);
});

// ── Boolean literals ────────────────────────────────────────────────────

it('translates boolean eq true', function () {
    $type = typedEntityType();
    $expr = translateFilter('active eq true', $type);
    expect($expr->right)->toBeInstanceOf(LiteralExpression::class);
    expect($expr->right->value)->toBe(true);
    expect($expr->right->edmType)->toBe('Edm.Boolean');
});

it('translates boolean eq false', function () {
    $type = typedEntityType();
    $expr = translateFilter('active eq false', $type);
    expect($expr->right->value)->toBe(false);
    expect($expr->right->edmType)->toBe('Edm.Boolean');
});

// ── Date/time literals (using typed properties to verify literal types) ──

it('translates date literal with typed property', function () {
    $type = typedEntityType();
    $expr = translateFilter('created eq 2000-01-01', $type);
    expect($expr->right)->toBeInstanceOf(LiteralExpression::class);
    expect($expr->right->edmType)->toBe('Edm.Date');
});

it('translates datetime literal with typed property', function () {
    $type = typedEntityType();
    $expr = translateFilter('modified eq 2000-01-01T12:34:59Z', $type);
    expect($expr->right->edmType)->toBe('Edm.DateTimeOffset');
});

it('translates time literal with typed property', function () {
    $type = typedEntityType();
    $expr = translateFilter('startTime eq 04:11:12', $type);
    expect($expr->right->edmType)->toBe('Edm.TimeOfDay');
});

it('translates GUID literal with typed property', function () {
    $type = typedEntityType();
    $expr = translateFilter('uid eq 4AA33245-E2D1-470D-9433-01AAFCF0C83F', $type);
    expect($expr->right->edmType)->toBe('Edm.Guid');
});

it('translates duration literal with typed property', function () {
    $type = typedEntityType();
    $expr = translateFilter('elapsed eq PT1M', $type);
    expect($expr->right->edmType)->toBe('Edm.Duration');
});

it('translates duration PT36H with typed property', function () {
    $type = typedEntityType();
    $expr = translateFilter('elapsed eq PT36H', $type);
    expect($expr->right->edmType)->toBe('Edm.Duration');
});

it('translates complex duration P10DT2H30M with typed property', function () {
    $type = typedEntityType();
    $expr = translateFilter('elapsed eq P10DT2H30M', $type);
    expect($expr->right->edmType)->toBe('Edm.Duration');
});

it('translates datetime with positive offset', function () {
    $type = typedEntityType();
    $expr = translateFilter('modified eq 2000-01-01T12:34:59+01:00', $type);
    expect($expr->right->edmType)->toBe('Edm.DateTimeOffset');
});

it('translates datetime with negative offset', function () {
    $type = typedEntityType();
    $expr = translateFilter('modified eq 2000-01-01T12:34:59-01:00', $type);
    expect($expr->right->edmType)->toBe('Edm.DateTimeOffset');
});

// ── Type hinting — literal type inferred from property ──────────────────

it('hints right-side literal type from left-side Int32 property', function () {
    $expr = translateFilter('id eq 4');
    expect($expr->right->edmType)->toBe('Edm.Int32');
});

it('hints right-side literal type from left-side String property', function () {
    $expr = translateFilter("origin eq 'test'");
    expect($expr->right->edmType)->toBe('Edm.String');
});

it('string property hint overrides literal default type', function () {
    // When origin (String) is on the left, the right-side date literal gets hinted as String
    $expr = translateFilter('origin eq 2000-01-01');
    expect($expr->right->edmType)->toBe('Edm.String');
});

it('double property hint applies to integer literal', function () {
    $type = typedEntityType();
    $expr = translateFilter('score eq 4', $type);
    expect($expr->right->edmType)->toBe('Edm.Double');
});

// ── Case-insensitive operators ──────────────────────────────────────────

it('translates case-insensitive operator EQ', function () {
    $expr = translateFilter("origin EQ 'lax'");
    expect($expr->operator)->toBe(BinaryOperator::Eq);
});

// ── Complex combined expressions ────────────────────────────────────────

it('translates and of two function comparisons', function () {
    $expr = translateFilter("endswith(origin,'Veniam et') eq true and startswith(origin,'Veniam et') eq true");
    expect($expr->operator)->toBe(BinaryOperator::And);
    expect($expr->left->left)->toBeInstanceOf(FunctionCallExpression::class);
    expect($expr->right->left)->toBeInstanceOf(FunctionCallExpression::class);
});

it('translates startswith eq true', function () {
    $expr = translateFilter("startswith(origin,'Veniam et') eq true");
    expect($expr->left)->toBeInstanceOf(FunctionCallExpression::class);
    expect($expr->left->name)->toBe('startswith');
    expect($expr->right->value)->toBe(true);
});

it('translates boolean eq startswith reversed', function () {
    $expr = translateFilter("true eq startswith(origin,'Veniam et')");
    expect($expr->left)->toBeInstanceOf(LiteralExpression::class);
    expect($expr->right)->toBeInstanceOf(FunctionCallExpression::class);
});

it('translates property gt datetime arithmetic', function () {
    $expr = translateFilter('origin gt (now() sub PT3M)');
    expect($expr->operator)->toBe(BinaryOperator::Gt);
    expect($expr->right)->toBeInstanceOf(BinaryExpression::class);
    expect($expr->right->operator)->toBe(BinaryOperator::Sub);
});

it('translates nested function calls with cast', function () {
    $expr = translateFilter("(contains(tolower(cast(origin, 'Edm.String')),'alpha')) or (contains(tolower(cast(origin, 'Edm.String')),'alpha'))");
    expect($expr)->toBeInstanceOf(BinaryExpression::class);
    expect($expr->operator)->toBe(BinaryOperator::Or);
});

it('translates and with negated function comparison', function () {
    $expr = translateFilter("endswith(origin,'Veniam et') eq true and not (startswith(origin,'Veniam et') eq true)");
    expect($expr->operator)->toBe(BinaryOperator::And);
    expect($expr->right)->toBeInstanceOf(UnaryExpression::class);
});

it('translates or with negated equality', function () {
    $expr = translateFilter("origin eq 'b' or not (origin eq 'a')");
    expect($expr->operator)->toBe(BinaryOperator::Or);
    expect($expr->right)->toBeInstanceOf(UnaryExpression::class);
});

it('translates float in arithmetic expression', function () {
    $expr = translateFilter('id add 3.14 eq 1.59');
    expect($expr->left->right)->toBeInstanceOf(LiteralExpression::class);
    expect($expr->left->right->value)->toBe(3.14);
});

it('translates chained add', function () {
    $expr = translateFilter('id add 3 add 5 eq 9');
    expect($expr->operator)->toBe(BinaryOperator::Eq);
    expect($expr->left)->toBeInstanceOf(BinaryExpression::class);
    expect($expr->left->operator)->toBe(BinaryOperator::Add);
});
