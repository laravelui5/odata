<?php

declare(strict_types=1);

use LaravelUi5\OData\Exception\BadRequestException;
use LaravelUi5\OData\Exception\ForbiddenException;
use LaravelUi5\OData\Exception\InternalServerErrorException;
use LaravelUi5\OData\Exception\NotFoundException;
use LaravelUi5\OData\Exception\NotImplementedException;

/**
 * `ProtocolException::report()` — 4xx client outcomes are suppressed from Laravel's error
 * log; 5xx server faults are still reported. Per Laravel's `report()` contract, a non-false
 * return skips default logging (suppressed) and a false return lets it log (reported).
 */
it('suppresses logging for 4xx client outcomes', function (object $e) {
    expect($e->report())->toBeTrue(); // non-false → Laravel skips its logger
})->with([
    'bad request (400)' => fn () => new BadRequestException(),
    'forbidden (403)'   => fn () => new ForbiddenException(),
    'not found (404)'   => fn () => new NotFoundException(),
]);

it('still reports 5xx server faults', function (object $e) {
    expect($e->report())->toBeFalse(); // false → Laravel performs its default logging
})->with([
    'internal server error (500)' => fn () => new InternalServerErrorException(),
    'not implemented (501)'       => fn () => new NotImplementedException(),
]);
