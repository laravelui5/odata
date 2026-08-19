<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Container\EntitySet;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Fixtures\DiscoveryFlightService;
use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Service\Builder\ResolverMapBuilder;
use LaravelUi5\OData\Tests\TestCase;

/**
 * The guards `odata:cache` grew after the 2026-08-19 incident in
 * `pragmatiqu.io`, where one run wrote empty ResolverMaps over working ones and
 * reached into `vendor/` — between them taking 25 entity sets dark and 103
 * feature tests with them.
 */
uses(TestCase::class);

/** A service that declares an entity set but binds no resolver for it. */
final class UnboundSetService extends ODataService
{
    public function namespace(): string
    {
        return 'Guard.Ns';
    }

    public function uri(): string
    {
        return 'guard';
    }

    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        $id   = new Property('id', new PrimitiveType(EdmPrimitiveType::Int32));
        $type = new EntityType(
            namespace: 'Guard.Ns',
            name: 'Widget',
            key: [$id],
            declaredProperties: [$id],
        );

        return $builder->namespace('Guard.Ns')
            ->addEntityType($type)
            ->addEntitySet(new EntitySet('Widgets', $type));
    }

    protected function registerBindings(ResolverMapBuilder $map): void
    {
        // Deliberately none — this is the shape the incident produced.
    }
}

/** The Edm dir the fixture services write into. */
function guardFixturesEdmDir(): string
{
    return dirname(__DIR__, 2) . '/tests-fixtures/Edm';
}

function rmGuardFixturesEdm(): void
{
    $dir = guardFixturesEdmDir();
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

/**
 * A service class that genuinely lives under `vendor/`.
 *
 * The rule under test is the file's location and nothing else, so the fixture
 * has to be a real file in a real vendor path rather than a stub with a
 * stubbed-out directory.
 */
function makeVendoredService(): ODataServiceInterface
{
    $dir = dirname(__DIR__, 2) . '/vendor/_odata_guard_fixture';

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $file = $dir . '/VendoredGuardService.php';

    if (!file_exists($file)) {
        file_put_contents($file, <<<'PHP'
        <?php

        final class VendoredGuardService extends \LaravelUi5\OData\ODataService
        {
            public function namespace(): string { return 'Vendored.Ns'; }
            public function uri(): string { return 'vendored'; }

            protected function configure(
                \LaravelUi5\OData\Service\Contracts\EdmBuilderInterface $builder
            ): \LaravelUi5\OData\Service\Contracts\EdmBuilderInterface {
                return $builder->namespace('Vendored.Ns');
            }
        }
        PHP);
    }

    require_once $file;

    return new VendoredGuardService();
}

function rmVendoredService(): void
{
    $dir = dirname(__DIR__, 2) . '/vendor/_odata_guard_fixture';
    if (is_dir($dir)) {
        @unlink($dir . '/VendoredGuardService.php');
        @rmdir($dir);
    }
}

beforeEach(fn () => rmGuardFixturesEdm());
afterEach(function () {
    rmGuardFixturesEdm();
    rmVendoredService();
});

function bindGuardRegistry(array $services): void
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

it('leaves every existing cache untouched when one service cannot be built', function () {
    // A service whose declared set has no binding is refused by the
    // RuntimeSchemaBuilder. The point under test is not that it fails — it
    // always did — but that it fails during the build pass, before a single
    // directory has been removed. Deleting as it went was the second half of
    // the 2026-08-19 incident: services already rewritten, later ones left with
    // no cache at all, inside `vendor/` recoverable only by reinstalling.
    bindGuardRegistry([new DiscoveryFlightService(), new UnboundSetService()]);

    expect(fn () => $this->artisan('odata:cache')->run())
        ->toThrow(RuntimeException::class);

    expect(is_dir(guardFixturesEdmDir()))->toBeFalse();
});

it('skips services that live in a package, and says so', function () {
    // Any class under vendor/ stands in for a packaged service — the rule is
    // the file's location, nothing else.
    bindGuardRegistry([makeVendoredService()]);

    $this->artisan('odata:cache')
        ->expectsOutputToContain('Skipped (lives in a package)')
        ->expectsOutputToContain('Nothing to cache')
        ->assertSuccessful();
});
