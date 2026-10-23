<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Http\CustomQueryOptions;
use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\AbstractEntitySet;
use LaravelUi5\OData\Service\Contracts\ColumnExpressionResolverInterface;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataEntity;
use LaravelUi5\OData\Tests\TestCase;

/*
 * The expression forward-exit: a property can be an expression on the query instead of its column, and
 * $filter, $orderby and $search compare, sort and search by it — here a text joined in from a second table,
 * with the column as fallback (the SDK's translated columns). Both paths; the value returned stays the row's.
 */

uses(TestCase::class);

#[ODataEntity(name: 'Fruit', entitySet: 'Fruits')]
class ExprFruit extends Model
{
    protected $table = 'expr_fruits';

    protected static function booted(): void
    {
        // the join the expression relies on is part of every query of the model; its columns must not
        // collide with the model's, or an unqualified `code` in the engine's WHERE turns ambiguous
        static::addGlobalScope('texts', fn (EloquentBuilder $q) => $q
            ->select('expr_fruits.*')
            ->leftJoin('expr_texts as __t', '__t.fruit', '=', 'expr_fruits.code'));
    }
}

final readonly class ExprFruitsSql extends AbstractEntitySet
{
    public function entitySetName(): string { return 'SqlFruits'; }
    public function key(): array { return ['id']; }
    public function columns(): array
    {
        return ['id' => EdmPrimitiveType::Int32, 'code' => EdmPrimitiveType::String, 'name' => EdmPrimitiveType::String];
    }

    public function query(CustomQueryOptions $options): Builder
    {
        return DB::table('expr_fruits')->select('expr_fruits.*')
            ->leftJoin('expr_texts as __t', '__t.fruit', '=', 'expr_fruits.code');
    }
}

final class ExprService extends ODataService
{
    public function __construct()
    {
        parent::__construct(serviceUriValue: '', namespaceValue: 'Test.Ns');
    }

    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        $this->discoverModel(ExprFruit::class);
        $this->discoverCustomEntitySet(ExprFruitsSql::class);

        return $builder->namespace($this->namespace());
    }
}

beforeEach(function () {
    $this->withExceptionHandling();

    DB::statement('CREATE TABLE expr_fruits (id integer primary key, code varchar(10), name varchar(20), created_at datetime, updated_at datetime)');
    DB::statement('CREATE TABLE expr_texts (fruit varchar(10), text varchar(20))');
    DB::table('expr_fruits')->insert([
        ['id' => 1, 'code' => 'apple',  'name' => 'Apple'],
        ['id' => 2, 'code' => 'banana', 'name' => 'Banana'],
        ['id' => 3, 'code' => 'cherry', 'name' => 'Cherry'],   // no text: the column answers
    ]);
    DB::table('expr_texts')->insert([['fruit' => 'apple', 'text' => 'Zebra'], ['fruit' => 'banana', 'text' => 'Apfel']]);

    $this->app->instance(ColumnExpressionResolverInterface::class, new class implements ColumnExpressionResolverInterface {
        public function expression(string $source, string $property): ?string
        {
            return in_array($source, [ExprFruit::class, ExprFruitsSql::class], true) && $property === 'name'
                ? 'coalesce(__t.text, expr_fruits.name)'
                : null;
        }
    });

    $service = new ExprService();
    $this->app->instance(ODataServiceRegistryInterface::class, new class ($service) implements ODataServiceRegistryInterface {
        public function __construct(private readonly ODataServiceInterface $service) {}
        public function resolve(string $fullPath): ODataServiceInterface { return $this->service; }
        public function services(): array { return [$this->service]; }
    });
});

/** @return array{int, list<int>|string, mixed} status, ids (or the error code), the response body */
function exprResult(object $test, string $set, array $query): array
{
    $response = $test->get("/odata/{$set}?" . http_build_query($query));
    $body     = $response->baseResponse instanceof Symfony\Component\HttpFoundation\StreamedResponse
        ? $response->streamedContent()
        : $response->getContent();
    $data     = json_decode($body, true);

    return [$response->getStatusCode(), isset($data['value']) ? array_column($data['value'], 'id') : ($data['error']['code'] ?? $body), $data];
}

foreach (['Fruits' => 'Eloquent', 'SqlFruits' => 'SQL'] as $set => $path) {
    describe("a property as expression on the {$path} path", function () use ($set) {
        it('filters by the expression', function (string $filter, array $ids) use ($set) {
            expect(array_slice(exprResult($this, $set, ['$orderby' => 'id', '$filter' => $filter]), 0, 2))->toBe([200, $ids]);
        })->with([
            'eq on the text'            => ["name eq 'Apfel'", [2]],
            'eq on the literal misses'  => ["name eq 'Banana'", []],
            'column where no text'      => ["name eq 'Cherry'", [3]],
            'contains'                  => ["contains(name, 'ebr')", [1]],
            'tolower around it'         => ["tolower(name) eq 'zebra'", [1]],
            'in'                        => ["name in ('Zebra', 'Cherry')", [1, 3]],
            'another property: column'  => ["code eq 'apple'", [1]],
        ]);

        it('sorts by the expression', function () use ($set) {
            expect(exprResult($this, $set, ['$orderby' => 'name'])[1])->toBe([2, 3, 1])          // Apfel, Cherry, Zebra
                ->and(exprResult($this, $set, ['$orderby' => 'name desc'])[1])->toBe([1, 3, 2]);
        });

        it('searches by the expression', function () use ($set) {
            // `apf` and `zeb` are in no column — only in the texts the expression yields
            expect(exprResult($this, $set, ['$orderby' => 'id', '$search' => 'apf'])[1])->toBe([2])
                ->and(exprResult($this, $set, ['$orderby' => 'id', '$search' => 'zeb'])[1])->toBe([1])
                ->and(exprResult($this, $set, ['$orderby' => 'id', '$search' => 'cherr'])[1])->toBe([3]);
        });

        it('counts what the expression filters, and returns the row’s own value', function () use ($set) {
            [, , $data] = exprResult($this, $set, ['$count' => 'true', '$filter' => "name eq 'Apfel'"]);

            expect($data['@odata.count'])->toBe(1)
                ->and($data['value'][0]['name'])->toBe('Banana');
        });
    });
}

it('keeps every property its column under the default resolver', function () {
    $this->app->forgetInstance(ColumnExpressionResolverInterface::class);
    $this->app->bind(ColumnExpressionResolverInterface::class, LaravelUi5\OData\Driver\Sql\ColumnsAsNamed::class);

    expect(array_slice(exprResult($this, 'Fruits', ['$orderby' => 'id', '$filter' => "name eq 'Banana'"]), 0, 2))->toBe([200, [2]]);
});
