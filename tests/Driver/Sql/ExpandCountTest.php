<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Fixtures\FlightServiceRegistry;
use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataEntity;
use LaravelUi5\OData\Tests\TestCase;

/*
 * OP15: `$expand=nav($count=true)` emits `nav@odata.count` — the size of the
 * collection under the expand's $filter, regardless of its $top/$skip. It used to
 * be parsed and dropped. A single-valued navigation answers 400, a virtual
 * expand 501 (its resolver decides which rows it returns).
 */

uses(TestCase::class);

#[ODataEntity(name: 'Order', entitySet: 'Orders')]
class CountOrder extends Model
{
    protected $table = 'count_orders';

    public function lines(): HasMany { return $this->hasMany(CountLine::class, 'order_id'); }
    public function customer(): BelongsTo { return $this->belongsTo(CountCustomer::class, 'customer_id'); }
}

#[ODataEntity(name: 'Line', entitySet: 'Lines')]
class CountLine extends Model
{
    protected $table = 'count_lines';

    public function notes(): HasMany { return $this->hasMany(CountNote::class, 'line_id'); }
}

#[ODataEntity(name: 'Note', entitySet: 'Notes')]
class CountNote extends Model
{
    protected $table = 'count_notes';
}

#[ODataEntity(name: 'Customer', entitySet: 'Customers')]
class CountCustomer extends Model
{
    protected $table = 'count_customers';
}

final class ExpandCountService extends ODataService
{
    public function __construct()
    {
        parent::__construct(serviceUriValue: '', namespaceValue: 'Test.Ns');
    }

    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        foreach ([CountOrder::class, CountLine::class, CountNote::class, CountCustomer::class] as $model) {
            $this->discoverModel($model);
        }

        return $builder->namespace($this->namespace());
    }
}

function bindExpandCountService(): void
{
    $service = new ExpandCountService();
    app()->instance(ODataServiceRegistryInterface::class, new class ($service) implements ODataServiceRegistryInterface {
        public function __construct(private readonly ODataServiceInterface $service) {}
        public function resolve(string $fullPath): ODataServiceInterface { return $this->service; }
        public function services(): array { return [$this->service]; }
    });
}

function expandCountJson(object $test, string $url): array
{
    $response = $test->get($url);
    $body     = $response->baseResponse instanceof Symfony\Component\HttpFoundation\StreamedResponse
        ? $response->streamedContent()
        : $response->getContent();

    return [$response->getStatusCode(), json_decode($body, true)];
}

beforeEach(function () {
    $this->withExceptionHandling();

    DB::statement('CREATE TABLE count_customers (id integer primary key, name varchar(20))');
    DB::statement('CREATE TABLE count_orders (id integer primary key, customer_id integer, label varchar(20))');
    DB::statement('CREATE TABLE count_lines (id integer primary key, order_id integer, sku varchar(20), qty integer)');
    DB::statement('CREATE TABLE count_notes (id integer primary key, line_id integer, text varchar(20))');

    DB::table('count_customers')->insert(['id' => 1, 'name' => 'acme']);
    DB::table('count_orders')->insert([['id' => 1, 'customer_id' => 1, 'label' => 'o1'], ['id' => 2, 'customer_id' => 1, 'label' => 'o2']]);
    DB::table('count_lines')->insert([
        ['id' => 1, 'order_id' => 1, 'sku' => 'a', 'qty' => 1],
        ['id' => 2, 'order_id' => 1, 'sku' => 'b', 'qty' => 5],
        ['id' => 3, 'order_id' => 1, 'sku' => 'c', 'qty' => 7],
    ]);
    DB::table('count_notes')->insert([['id' => 1, 'line_id' => 1, 'text' => 'x'], ['id' => 2, 'line_id' => 1, 'text' => 'y']]);
});

describe('$count inside $expand', function () {
    beforeEach(fn () => bindExpandCountService());

    it('emits the size of each expanded collection, before the collection', function () {
        [$status, $data] = expandCountJson($this, '/odata/Orders?$orderby=id&$expand=lines($count=true)');

        expect($status)->toBe(200)
            ->and(array_column($data['value'], 'lines@odata.count'))->toBe([3, 0])
            ->and(array_keys($data['value'][0]))->toBe(['id', 'customer_id', 'label', 'lines@odata.count', 'lines'])
            ->and(json_encode($data))->not->toContain('__odata_count');
    });

    it('counts under the expand\'s $filter but not its $top', function () {
        [, $data] = expandCountJson($this, '/odata/Orders(1)?$expand=lines($filter=qty gt 2;$top=1;$count=true)');

        expect($data['lines@odata.count'])->toBe(2)
            ->and($data['lines'])->toHaveCount(1);
    });

    it('counts at a nested level', function () {
        [, $data] = expandCountJson($this, '/odata/Orders(1)?$expand=lines($orderby=id;$expand=notes($count=true))');

        expect(array_column($data['lines'], 'notes@odata.count'))->toBe([2, 0, 0]);
    });

    it('keeps the count with $select', function () {
        [, $data] = expandCountJson($this, '/odata/Orders?$orderby=id&$select=label&$expand=lines($count=true)');

        expect(array_keys($data['value'][0]))->toBe(['label', 'lines@odata.count', 'lines']);
    });

    it('refuses $count on a single-valued navigation', function () {
        [$status, $data] = expandCountJson($this, '/odata/Orders?$expand=customer($count=true)');

        expect([$status, $data['error']['code']])->toBe([400, 'invalid_expand']);
    });
});

it('refuses $count on a virtual expand', function () {
    $this->app->instance(ODataServiceRegistryInterface::class, new FlightServiceRegistry());
    DB::table('flights')->insert(['id' => 1, 'origin' => 'lhr', 'destination' => 'lax']);

    [$status, $data] = expandCountJson($this, '/odata/Flights(1)?$expand=stats($count=true)');

    expect([$status, $data['error']['code']])->toBe([501, 'unsupported_expand']);
});
