<?php

declare(strict_types=1);

use LaravelUi5\OData\Fixtures\Cached\Flights\CachedFlightService;
use LaravelUi5\OData\Fixtures\DiscoveryFlightService;
use LaravelUi5\OData\Fixtures\FlightService;
use LaravelUi5\OData\Fixtures\Models\Flight;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Service\Serialization\CsdlSerializer;
use LaravelUi5\OData\Tests\TestCase;

/**
 * `odata:cache --class=FQCN1,FQCN2` — cache route-composed (bound) services that are not in
 * the ODataServiceRegistryInterface, plus the fail-loud guards around it.
 */
uses(TestCase::class);

/** The Edm dir of the shared tests-fixtures/ (never written: it holds many services), or of a cached fixture. */
function fixturesEdmDir(?string $cached = null): string
{
    return dirname(__DIR__, 2) . ($cached === null ? '/tests-fixtures/Edm' : "/tests-fixtures/Cached/{$cached}/Edm");
}

function rmFixturesEdm(): void
{
    foreach ([fixturesEdmDir(), fixturesEdmDir('Flights')] as $dir) {
        if (!is_dir($dir)) {
            continue;
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
}

/** Where odata:cache writes the cached flight fixture's $metadata (OP12). */
function cachedFlightsXml(): string
{
    return resource_path('edmx/LaravelUi5.OData.Fixtures.Cached.Flights.xml');
}

beforeEach(function () {
    rmFixturesEdm();
    @unlink(cachedFlightsXml());
});
afterEach(function () {
    rmFixturesEdm();
    @unlink(cachedFlightsXml());
    @rmdir(resource_path('edmx')); // only when empty
});

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

    $this->artisan('odata:cache', ['--class' => CachedFlightService::class])
        ->expectsOutputToContain('Cached:')
        ->assertSuccessful();

    expect(file_exists(fixturesEdmDir('Flights') . '/Edmx.php'))->toBeTrue();
});

it('fails loud when another service lives beside the one it caches, cached or not (OP01)', function () {
    // The 2026-06-11 case: a route-composed service beside a cached one finds that one's
    // Edm/ by namespace and serves its schema. Only DiscoveryFlightService is cached here;
    // the others in tests-fixtures/ are in no registry and no --class — and still count.
    bindRegistry([]);

    $this->artisan('odata:cache', ['--class' => DiscoveryFlightService::class])
        ->expectsOutputToContain('Cache directory collision')
        ->expectsOutputToContain('Each OData service must live in its own namespace/directory.')
        ->assertFailed();

    expect(is_dir(fixturesEdmDir()))->toBeFalse();
});

it('writes the finished $metadata document beside the Edm cache, and odata:clear removes it (OP12)', function () {
    bindRegistry([]);

    $this->artisan('odata:cache', ['--class' => CachedFlightService::class])
        ->expectsOutputToContain('Wrote ' . cachedFlightsXml())
        ->assertSuccessful();

    $cold = (new CsdlSerializer())->serialize((new CachedFlightService())->buildForCache()[0]);
    expect(file_get_contents(cachedFlightsXml()))->toBe($cold);

    $this->artisan('odata:clear', ['--class' => CachedFlightService::class])->assertSuccessful();
    expect(file_exists(cachedFlightsXml()))->toBeFalse();
});

it('never writes or deletes a document outside resources/edmx — a service that ships its own', function () {
    $own = tempnam(sys_get_temp_dir(), 'edmx');
    file_put_contents($own, '<hand-written/>');
    $service = new class ($own) extends LaravelUi5\OData\ODataService {
        public function __construct(private string $own) { parent::__construct(); }
        public function cachedMetadataXMLPath(): string { return $this->own; }
        protected function configure(LaravelUi5\OData\Service\Contracts\EdmBuilderInterface $builder): LaravelUi5\OData\Service\Contracts\EdmBuilderInterface
        {
            return $builder->namespace('Own.Ns');
        }
    };
    bindRegistry([$service]);

    $this->artisan('odata:clear')->assertSuccessful();

    expect(file_get_contents($own))->toBe('<hand-written/>');
    unlink($own);
});

