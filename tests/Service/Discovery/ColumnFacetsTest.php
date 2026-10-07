<?php

declare(strict_types=1);

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Edm\Contracts\EdmxInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\Type\TypeFacets;
use LaravelUi5\OData\ODataServiceProvider;
use LaravelUi5\OData\Service\Builder\EdmBuilder;
use LaravelUi5\OData\Service\Cache\EdmxWriter;
use LaravelUi5\OData\Service\Contracts\ColumnFacetResolverInterface;
use LaravelUi5\OData\Service\Discovery\ColumnFacetsAsDeclared;
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
        set(?string $value) { $this->setAttribute('note', $value); }
    }
}

/** Stands in for a package cast that marks a column as a unit price (the SDK's price cast). */
class ColumnFacetsUnitPriceCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }
}

/** `amount` carries the unit-price cast, so a resolver can recognise it. */
class ColumnFacetsCastPrice extends Model
{
    protected $table = 'column_facets_prices';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount' => ColumnFacetsUnitPriceCast::class];
    }
}

/** Same cast, plus a literal Scale on the attribute — the attribute wins over a resolver. */
class ColumnFacetsCastPriceWithAttribute extends ColumnFacetsCastPrice
{
    #[ODataProperty(scale: 2)]
    public ?string $amount {
        get => $this->getAttribute('amount');
        set(?string $value) { $this->setAttribute('amount', $value); }
    }
}

/** A literal Precision/Scale override on the attribute. */
class ColumnFacetsLiteralScale extends Model
{
    protected $table = 'column_facets_prices';
    public $timestamps = false;
    protected $guarded = [];

    #[ODataProperty(precision: 15, scale: 3)]
    public ?string $amount {
        get => $this->getAttribute('amount');
        set(?string $value) { $this->setAttribute('amount', $value); }
    }
}

/** Scale above Precision — refused at schema build. */
class ColumnFacetsScaleTooLarge extends Model
{
    protected $table = 'column_facets_prices';
    public $timestamps = false;
    protected $guarded = [];

    #[ODataProperty(precision: 4, scale: 6)]
    public ?string $amount {
        get => $this->getAttribute('amount');
        set(?string $value) { $this->setAttribute('amount', $value); }
    }
}

/** Scale on a string column — refused at schema build. */
class ColumnFacetsScaleOnString extends Model
{
    protected $table = 'column_facets_prices';
    public $timestamps = false;
    protected $guarded = [];

    #[ODataProperty(scale: 2)]
    public ?string $code {
        get => $this->getAttribute('code');
        set(?string $value) { $this->setAttribute('code', $value); }
    }
}

/**
 * Announces the installation's price decimals on every column cast as a unit price,
 * and records what it was asked.
 */
class ColumnFacetsInstallationResolver implements ColumnFacetResolverInterface
{
    /** @var list<array{string, string, ?string}> */
    public array $calls = [];

    public function __construct(private readonly int $priceDecimals = 4) {}

    public function resolve(string $modelClass, string $column, ?string $cast, TypeFacets $facets): TypeFacets
    {
        $this->calls[] = [$modelClass, $column, $cast];

        return $cast === ColumnFacetsUnitPriceCast::class ? $facets->withScale($this->priceDecimals) : $facets;
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
function discoverFacetsEdmx(array $models, ?ColumnFacetResolverInterface $resolver = null): EdmxInterface
{
    $discovery = new ModelDiscovery($resolver);
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

describe('ModelDiscovery → facet overrides', function () {
    it('binds the pass-through resolver by default', function () {
        expect(app(ColumnFacetResolverInterface::class))->toBeInstanceOf(ColumnFacetsAsDeclared::class);
    });

    it('keeps a resolver bound before the provider registers', function () {
        $this->app->bind(ColumnFacetResolverInterface::class, ColumnFacetsInstallationResolver::class);

        (new ODataServiceProvider($this->app))->register();

        expect(app(ColumnFacetResolverInterface::class))->toBeInstanceOf(ColumnFacetsInstallationResolver::class);
    });

    it('takes Precision and Scale from the attribute', function () {
        $facets = facetsEntityType(discoverFacetsEdmx([ColumnFacetsLiteralScale::class]))
            ->getProperty('amount')->getFacets();

        expect($facets->getPrecision())->toBe(15)
            ->and($facets->getScale())->toBe(3);
    });

    it('lets a bound resolver announce Scale on a column it recognises by its cast', function () {
        $resolver = new ColumnFacetsInstallationResolver(priceDecimals: 4);
        $type     = facetsEntityType(discoverFacetsEdmx([ColumnFacetsCastPrice::class], $resolver));

        expect($type->getProperty('amount')->getFacets()->getScale())->toBe(4)
            ->and($type->getProperty('amount')->getFacets()->getPrecision())->toBe(19)
            ->and($type->getProperty('percentage')->getFacets()->getScale())->toBe(0)
            ->and($resolver->calls)->toContain([ColumnFacetsCastPrice::class, 'amount', ColumnFacetsUnitPriceCast::class])
            ->and($resolver->calls)->toContain([ColumnFacetsCastPrice::class, 'code', null]);
    });

    it('resolves through the container when no resolver is passed', function () {
        $this->app->instance(ColumnFacetResolverInterface::class, new ColumnFacetsInstallationResolver(priceDecimals: 5));

        $facets = facetsEntityType(discoverFacetsEdmx([ColumnFacetsCastPrice::class]))
            ->getProperty('amount')->getFacets();

        expect($facets->getScale())->toBe(5);
    });

    it('lets the attribute win over the resolver', function () {
        $resolver = new ColumnFacetsInstallationResolver(priceDecimals: 4);
        $facets   = facetsEntityType(discoverFacetsEdmx([ColumnFacetsCastPriceWithAttribute::class], $resolver))
            ->getProperty('amount')->getFacets();

        expect($facets->getScale())->toBe(2);
    });

    it('refuses a Scale above the Precision', function () {
        discoverFacetsEdmx([ColumnFacetsScaleTooLarge::class]);
    })->throws(LogicException::class, 'Scale 6 exceeds Precision 4');

    it('refuses a Scale on a type that cannot carry one', function () {
        discoverFacetsEdmx([ColumnFacetsScaleOnString::class]);
    })->throws(LogicException::class, 'Scale is only valid on Edm.Decimal');

    it('freezes the resolved Scale into the cache and serves it warm as cold', function () {
        $cold = discoverFacetsEdmx([ColumnFacetsCastPrice::class], new ColumnFacetsInstallationResolver(priceDecimals: 4));
        $warm = writeAndLoadFacets($cold, $this->tmpDir . '/resolved', 'ColumnFacetsResolvedTest\\Edm');

        $serializer = new CsdlSerializer();

        expect($serializer->serialize($warm))
            ->toBe($serializer->serialize($cold))
            ->toContain('Name="amount" Type="Edm.Decimal" Nullable="false" Precision="19" Scale="4"');
    });
});
