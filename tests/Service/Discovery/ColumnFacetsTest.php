<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Edm\Contracts\EdmxInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Service\Builder\EdmBuilder;
use LaravelUi5\OData\Service\Cache\EdmxWriter;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataProperty;
use LaravelUi5\OData\Service\Discovery\ModelDiscovery;
use LaravelUi5\OData\Service\Serialization\CsdlSerializer;
use LaravelUi5\OData\Tests\TestCase;

uses(TestCase::class);

/** Fixture model over a table whose declared column types carry the facets. */
class ColumnFacetsPrice extends Model
{
    protected $table = 'column_facets_prices';
    public $timestamps = false;
    protected $guarded = [];
}

/**
 * Same table; `note` is declared non-nullable through the attribute, carried on
 * a hooked property so the attribute bag is not shadowed.
 */
class ColumnFacetsOverride extends Model
{
    protected $table = 'column_facets_prices';
    public $timestamps = false;
    protected $guarded = [];

    #[ODataProperty(nullable: false)]
    public ?string $note {
        get => $this->getAttribute('note');
        set(?string $value) => $this->setAttribute('note', $value);
    }
}

/**
 * SQLite keeps the declared type text, so a raw table gives discovery the same
 * `decimal(19,6)` / `varchar(40)` a MySQL column reports. Laravel's SQLite
 * grammar would write `numeric` and `varchar` without arguments.
 */
function createColumnFacetsTable(): void
{
    DB::statement('DROP TABLE IF EXISTS column_facets_prices');
    DB::statement(<<<'SQL'
        CREATE TABLE column_facets_prices (
            id integer primary key,
            code varchar(40) not null,
            amount decimal(19,6) not null,
            percentage decimal(9) null,
            note varchar(255) null,
            description text null
        )
        SQL);
}

/** @param list<class-string<Model>> $models */
function discoverFacetsEdmx(array $models): EdmxInterface
{
    $discovery = new ModelDiscovery();
    foreach ($models as $model) {
        $discovery->add($model);
    }

    $builder = (new EdmBuilder())->namespace('Test.Ns');
    $discovery->apply($builder, 'Test.Ns');

    return $builder->build();
}

function facetsEntityType(EdmxInterface $edmx): EntityTypeInterface
{
    return $edmx->getSchema('Test.Ns')->getEntityTypes()[0];
}

function writeAndLoadFacets(EdmxInterface $edmx, string $dir, string $namespace): EdmxInterface
{
    (new EdmxWriter($edmx, $dir . '/Edm', $namespace))->write();

    spl_autoload_register(function (string $class) use ($namespace, $dir) {
        if (!str_starts_with($class, $namespace . '\\')) {
            return;
        }
        $file = $dir . '/Edm/' . str_replace('\\', '/', substr($class, strlen($namespace) + 1)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });

    require_once $dir . '/Edm/Edmx.php';
    $edmxClass = $namespace . '\\Edmx';

    return new $edmxClass();
}

beforeEach(function () {
    createColumnFacetsTable();
    $this->tmpDir = sys_get_temp_dir() . '/column_facets_test_' . getmypid();
});

afterEach(function () {
    if (is_dir($this->tmpDir)) {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tmpDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->tmpDir);
    }
});

describe('ModelDiscovery → type facets from the column schema', function () {
    it('takes Precision and Scale from a decimal(p,s) column', function () {
        $facets = facetsEntityType(discoverFacetsEdmx([ColumnFacetsPrice::class]))
            ->getProperty('amount')->getFacets();

        expect($facets->getPrecision())->toBe(19)
            ->and($facets->getScale())->toBe(6)
            ->and($facets->isNullable())->toBeFalse();
    });

    it('reads decimal(p) as Scale 0', function () {
        $facets = facetsEntityType(discoverFacetsEdmx([ColumnFacetsPrice::class]))
            ->getProperty('percentage')->getFacets();

        expect($facets->getPrecision())->toBe(9)
            ->and($facets->getScale())->toBe(0)
            ->and($facets->isNullable())->toBeTrue();
    });

    it('takes MaxLength from a varchar(n) column', function () {
        $facets = facetsEntityType(discoverFacetsEdmx([ColumnFacetsPrice::class]))
            ->getProperty('code')->getFacets();

        expect($facets->getMaxLength())->toBe(40)
            ->and($facets->isNullable())->toBeFalse();
    });

    it('never declares a key nullable, even where SQLite reports it so', function () {
        $facets = facetsEntityType(discoverFacetsEdmx([ColumnFacetsPrice::class]))
            ->getProperty('id')->getFacets();

        expect($facets->isNullable())->toBeFalse();
    });

    it('leaves an unconstrained nullable column without facets', function () {
        $property = facetsEntityType(discoverFacetsEdmx([ColumnFacetsPrice::class]))
            ->getProperty('description');

        expect($property->getFacets())->toBeNull();
    });

    it('lets #[ODataProperty(nullable:)] win over the column', function () {
        $type = facetsEntityType(discoverFacetsEdmx([ColumnFacetsOverride::class]));

        expect($type->getProperty('note')->getFacets()->isNullable())->toBeFalse()
            ->and($type->getProperty('note')->getFacets()->getMaxLength())->toBe(255);
    });

    it('writes the facets into $metadata', function () {
        $xml = (new CsdlSerializer())->serialize(discoverFacetsEdmx([ColumnFacetsPrice::class]));

        expect($xml)
            ->toContain('Name="amount" Type="Edm.Decimal" Nullable="false" Precision="19" Scale="6"')
            ->toContain('Name="code" Type="Edm.String" Nullable="false" MaxLength="40"')
            ->toContain('Name="description" Type="Edm.String"/>');
    });
});

describe('odata:cache → facets survive the warm path', function () {
    it('carries every facet into the cached property', function () {
        $cold = discoverFacetsEdmx([ColumnFacetsPrice::class]);
        $warm = writeAndLoadFacets($cold, $this->tmpDir . '/props', 'ColumnFacetsPropsTest\\Edm');

        foreach (facetsEntityType($cold)->getDeclaredProperties() as $property) {
            $cached = facetsEntityType($warm)->getProperty($property->getName());
            $facets = $property->getFacets();

            if ($facets === null) {
                expect($cached->getFacets())->toBeNull();
                continue;
            }

            expect([
                $cached->getFacets()->isNullable(),
                $cached->getFacets()->getMaxLength(),
                $cached->getFacets()->getPrecision(),
                $cached->getFacets()->getScale(),
                $cached->getFacets()->isUnicode(),
                $cached->getFacets()->getSrid(),
            ])->toBe([
                $facets->isNullable(),
                $facets->getMaxLength(),
                $facets->getPrecision(),
                $facets->getScale(),
                $facets->isUnicode(),
                $facets->getSrid(),
            ]);
        }
    });

    it('serves the same $metadata warm and cold', function () {
        $cold = discoverFacetsEdmx([ColumnFacetsPrice::class]);
        $warm = writeAndLoadFacets($cold, $this->tmpDir . '/xml', 'ColumnFacetsXmlTest\\Edm');

        $serializer = new CsdlSerializer();

        expect($serializer->serialize($warm))->toBe($serializer->serialize($cold));
    });
});
