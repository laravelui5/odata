<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Exception\InternalServerErrorException;
use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\Contracts\ColumnExpressionResolverInterface;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataAnnotate;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataComputed;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataEntity;
use LaravelUi5\OData\Tests\TestCase;
use LaravelUi5\OData\Vocabularies\Measures\V1\ISOCurrency;
use LaravelUi5\OData\Vocabularies\Measures\V1\Unit;

/*
 * OP34 (2026-11-02): a computed property is declared on the model's class with #[ODataComputed]; its value is the
 * expression the bound ColumnExpressionResolverInterface answers, selected as `(<expression>) AS <name>`. The case
 * from Foundation D175/D178: the base currency as a literal per request, the unit code beside `unit_id`.
 */

uses(TestCase::class);

#[ODataEntity(name: 'Item', entitySet: 'Items')]
#[ODataComputed('currency', EdmPrimitiveType::String, nullable: false, maxLength: 3)]
#[ODataComputed('unit', EdmPrimitiveType::String, maxLength: 3)]
#[ODataAnnotate('price', new ISOCurrency(new Path('currency')))]
#[ODataAnnotate('quantity', new Unit(new Path('unit')))]
class ComputedItem extends Model
{
    protected $table = 'computed_items';
    public $timestamps = false;
}

#[ODataEntity(name: 'Order', entitySet: 'Orders')]
class ComputedOrder extends Model
{
    protected $table = 'computed_orders';
    public $timestamps = false;

    public function items(): HasMany
    {
        return $this->hasMany(ComputedItem::class, 'order_id');
    }
}

#[ODataComputed('price', EdmPrimitiveType::String)]
class ComputedCollision extends Model
{
    protected $table = 'computed_items';
    public $timestamps = false;
}

#[ODataComputed('secret', EdmPrimitiveType::String)]
class ComputedHidden extends Model
{
    protected $table = 'computed_items';
    public $timestamps = false;
    protected $hidden = ['secret'];
}

/** Stands in for the Foundation: the tenant's base currency, the code of a unit-of-measure code list. */
final class ComputedExpressions implements ColumnExpressionResolverInterface
{
    public function __construct(private readonly string $baseCurrency = 'EUR') {}

    public function expression(string $source, string $property): ?string
    {
        if ($source !== ComputedItem::class) {
            return null;
        }

        return match ($property) {
            'currency' => "'" . $this->baseCurrency . "'",
            'unit'     => '(select code from computed_units where computed_units.id = computed_items.unit_id)',
            default    => null,
        };
    }
}

final class ComputedService extends ODataService
{
    /** @var list<class-string<Model>> */
    public static array $models = [ComputedItem::class, ComputedOrder::class];

    public function __construct()
    {
        parent::__construct(serviceUriValue: '', namespaceValue: 'Test.Ns');
    }

    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        foreach (self::$models as $model) {
            $this->discoverModel($model);
        }

        return $builder->namespace($this->namespace());
    }
}

function computedGet(string $url): array
{
    $response = test()->get($url);
    $response->assertStatus(200);

    return json_decode($response->streamedContent(), true);
}

beforeEach(function () {
    $this->withExceptionHandling();
    ComputedService::$models = [ComputedItem::class, ComputedOrder::class];
    $this->app->instance(ColumnExpressionResolverInterface::class, new ComputedExpressions());

    DB::statement('CREATE TABLE computed_units (id integer primary key, code varchar(3) not null)');
    DB::statement('CREATE TABLE computed_orders (id integer primary key)');
    DB::statement('CREATE TABLE computed_items (id integer primary key, order_id integer not null, price decimal(19,6) not null, quantity decimal(19,6) not null, unit_id integer null)');
    DB::table('computed_units')->insert([['id' => 1, 'code' => 'KGM'], ['id' => 2, 'code' => 'LTR']]);
    DB::table('computed_orders')->insert(['id' => 1]);
    DB::table('computed_items')->insert([
        ['id' => 10, 'order_id' => 1, 'price' => 12.5, 'quantity' => 2, 'unit_id' => 1],
        ['id' => 11, 'order_id' => 1, 'price' => 3, 'quantity' => 1, 'unit_id' => 2],
        ['id' => 12, 'order_id' => 1, 'price' => 1, 'quantity' => 4, 'unit_id' => null],
    ]);

    $service = new ComputedService();
    $this->app->instance(ODataServiceRegistryInterface::class, new class ($service) implements ODataServiceRegistryInterface {
        public function __construct(private readonly ODataServiceInterface $service) {}
        public function resolve(string $fullPath): ODataServiceInterface { return $this->service; }
        public function services(): array { return [$this->service]; }
    });
});

describe('the declaration', function () {
    it('declares the computed property with its type, its facets and Core.Computed', function () {
        expect($this->get('/odata/$metadata')->streamedContent())
            ->toContain('<Property Name="currency" Type="Edm.String" Nullable="false" MaxLength="3"><Annotation Term="Org.OData.Core.V1.Computed"/></Property>')
            ->toContain('<Property Name="unit" Type="Edm.String" Nullable="true" MaxLength="3"><Annotation Term="Org.OData.Core.V1.Computed"/></Property>');
    });

    it('lets a column annotation point at it', function () {
        expect($this->get('/odata/$metadata')->streamedContent())
            ->toContain('<Annotation Term="Org.OData.Measures.V1.ISOCurrency" Path="currency"/>')
            ->toContain('<Annotation Term="Org.OData.Measures.V1.Unit" Path="unit"/>');
    });
});

describe('the value', function () {
    it('goes out on every row of a collection', function () {
        $rows = computedGet('/odata/Items')['value'];

        expect(array_column($rows, 'currency'))->toBe(['EUR', 'EUR', 'EUR'])
            ->and(array_column($rows, 'unit'))->toBe(['KGM', 'LTR', null])
            ->and($rows[0])->toHaveKeys(['id', 'order_id', 'price', 'quantity', 'unit_id']);
    });

    it('goes out on a single entity', function () {
        expect(computedGet('/odata/Items(11)'))->toMatchArray(['currency' => 'EUR', 'unit' => 'LTR']);
    });

    it('can be selected alone or beside columns', function () {
        expect(computedGet('/odata/Items?$select=unit')['value'][0])->toBe(['unit' => 'KGM'])
            ->and(computedGet('/odata/Items(10)?$select=price,currency'))->toMatchArray(['price' => 12.5, 'currency' => 'EUR'])
            ->and(computedGet('/odata/Items(10)?$select=price,currency'))->not->toHaveKey('unit');
    });

    it('filters and sorts by its expression', function () {
        expect(array_column(computedGet("/odata/Items?\$filter=unit eq 'LTR'")['value'], 'id'))->toBe([11])
            ->and(array_column(computedGet('/odata/Items?$filter=unit ne null&$orderby=unit desc')['value'], 'id'))->toBe([11, 10]);
    });

    it('goes out on an expanded row, with or without a nested $select', function () {
        $items = computedGet('/odata/Orders(1)?$expand=items')['items'];
        expect(array_column($items, 'unit'))->toBe(['KGM', 'LTR', null]);

        $items = computedGet('/odata/Orders?$expand=items($select=id,unit)')['value'][0]['items'];
        expect($items[0])->toMatchArray(['id' => 10, 'unit' => 'KGM'])
            ->and($items[0])->not->toHaveKey('currency');
    });

    it('is readable by $compute', function () {
        expect(computedGet("/odata/Items?\$compute=concat(unit,'!') as x")['value'][0]['x'])->toBe('KGM!');
    });

    it('follows the resolver: another base currency, another value', function () {
        $this->app->instance(ColumnExpressionResolverInterface::class, new ComputedExpressions('CHF'));

        expect(computedGet('/odata/Items(10)')['currency'])->toBe('CHF');
    });
});

describe('it fails loud', function () {
    it('when no expression answers it', function () {
        $this->app->instance(ColumnExpressionResolverInterface::class, new class implements ColumnExpressionResolverInterface {
            public function expression(string $source, string $property): ?string { return null; }
        });
        $this->withoutExceptionHandling();

        $this->get('/odata/Items')->streamedContent();
    })->throws(InternalServerErrorException::class, 'The computed property "currency" of ComputedItem has no expression');

    it('when it takes the name of a column', function () {
        ComputedService::$models = [ComputedCollision::class];

        (new ComputedService())->schema();
    })->throws(LogicException::class, '#[ODataComputed] on ComputedCollision declares "price", which entity type "ComputedCollision" already has as a column');

    it('when the model never serializes it', function () {
        ComputedService::$models = [ComputedHidden::class];

        (new ComputedService())->schema();
    })->throws(LogicException::class, '#[ODataComputed] on ComputedHidden declares "secret", which the model never serializes');
});
