<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Tests;

use Pest\Support\HigherOrderTapProxy;
use UnexpectedValueException;

/*
 * Helpers shared by every test directory.
 */

/**
 * `$value` as an instance of `$class`, or a failed test: asserts at runtime and tells PHPStan the type, which a Pest
 * expectation does not. Use it where a test goes on to read what the type promises
 * (`narrow(BinaryExpression::class, $expr)->operator`), instead of `expect($expr)->toBeInstanceOf(…)` (OP32).
 *
 * @template T of object
 * @param class-string<T> $class
 * @return T
 */
function narrow(string $class, mixed $value): object
{
    // Counted as an assertion, so a test that only checks the type is not reported as risky.
    expect($value)->toBeInstanceOf($class);
    if (!$value instanceof $class) {
        throw new UnexpectedValueException(sprintf('Expected %s, got %s.', $class, get_debug_type($value)));
    }

    return $value;
}

/**
 * The running test case, for a helper function that sends a request or asserts outside the test's closure. Pest's
 * `test()` hands it out wrapped in a `HigherOrderTapProxy`, whose type PHPStan cannot see through.
 */
function testCase(): TestCase
{
    return narrow(TestCase::class, narrow(HigherOrderTapProxy::class, test())->target);
}
