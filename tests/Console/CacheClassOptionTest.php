<?php

declare(strict_types=1);

use LaravelUi5\OData\Fixtures\DiscoveryFlightService;
use LaravelUi5\OData\Fixtures\FlightService;
use LaravelUi5\OData\Fixtures\Models\Flight;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Tests\TestCase;

/**
 * `odata:cache --class=FQCN1,FQCN2` — cache route-composed (bound) services that are not in
 * the ODataServiceRegistryInterface, plus the fail-loud guards around it.
 */
uses(TestCase::class);

function fixturesEdmDir(): string
{
    return dirname(__DIR__, 2) . '/tests-fixtures/Edm';
}

function rmFixturesEdm(): void
{
    $dir = fixturesEdmDir();
    if (!is_dir($dir)) {
        return;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($dir);
}

beforeEach(fn () => rmFixturesEdm());
afterEach(fn () => rmFixturesEdm());

/** Bind a registry returning the given services (resolve() unused by the cache command). */
function bindRegistry(array $services): void
{
    app()->instance(
        ODataServiceRegistryInterface::class,
        new class($services) implements ODataServiceRegistryInterface {
            public function __construct(private array $services) {}
            public function services(): array { return $this->services; }
            public function resolve(string $fullPath): ODataServiceInterface { return $this->services[0]; }
        },
    );
}

it('fails loud on an unknown --class', function () {
    bindRegistry([]);

    $this->artisan('odata:cache', ['--class' => 'Nope\\Missing'])
        ->expectsOutputToContain('Class not found')
        ->assertFailed();
});

it('fails loud when a --class is not an OData service', function () {
    bindRegistry([]);

    $this->artisan('odata:cache', ['--class' => Flight::class])
        ->expectsOutputToContain('does not implement ODataServiceInterface')
        ->assertFailed();
});

it('fails loud on a cache-dir collision between co-located services (writes nothing)', function () {
    // FlightService (registry) and DiscoveryFlightService (--class) share tests-fixtures/ →
    // same Edm/ dir. The pre-pass must fail BEFORE writing anything.
    bindRegistry([new FlightService()]);

    $this->artisan('odata:cache', ['--class' => DiscoveryFlightService::class])
        ->expectsOutputToContain('collision')
        ->assertFailed();

    expect(is_dir(fixturesEdmDir()))->toBeFalse();
});

it('caches a --class service that is not in the registry', function () {
    bindRegistry([]); // registry empty — only the --class service gets cached

    $this->artisan('odata:cache', ['--class' => DiscoveryFlightService::class])
        ->expectsOutputToContain('Cached:')
        ->assertSuccessful();

    expect(file_exists(fixturesEdmDir() . '/Edmx.php'))->toBeTrue();
});
