<?php

declare(strict_types=1);

/*
 * Pest bootstrap file.
 *
 * Add uses() calls here as Pest tests are introduced to each directory.
 *
 * Tier-4 tests (Laravel + HTTP) need the full Orchestra TestCase:
 *   uses(LaravelUi5\OData\Tests\TestCase::class)->in('EntitySet', 'Filter', ...);
 *
 * Tier-1 unit tests (Edm\, Vocabularies\, Service\Discovery\) need no
 * base class and require no uses() call at all.
 */

require_once __DIR__ . '/Protocol/Parser/Helpers.php';
