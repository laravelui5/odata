<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Http\CustomQueryOptions;
use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\AbstractEntitySet;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataEntity;
use LaravelUi5\OData\Tests\TestCase;

/*
 * OP06: what the filter translators cannot translate, they refuse (501
 * unsupported_filter); a malformed comparison is a 400. Before, an unsupported
 * function or operator answered 200 with either every row or none. Same table,
 * both paths; tolower/toupper as operands are supported (UI5's case-insensitive
 * filters send `tolower(Name) eq tolower('x')`).
 */

uses(TestCase::class);

#[ODataEntity(name: 'Item', entitySet: 'Items')]
class FilterItem extends Model
{
    protected $table = 'filter_items';
    protected $casts = ['is_big' => 'boolean'];

    public function parts(): HasMany
    {
        return $this->hasMany(FilterPart::class, 'item_id');
    }
}

#[ODataEntity(name: 'Part', entitySet: 'Parts')]
class FilterPart extends Model
{
    protected $table = 'filter_parts';
}

final readonly class FilterItemsSql extends AbstractEntitySet
{
    public function entitySetName(): string { return 'SqlItems'; }
    public function key(): array { return ['id']; }
    public function columns(): array
    {
        return [
            'id'     => EdmPrimitiveType::Int32,
            'label'  => EdmPrimitiveType::String,
            'origin' => EdmPrimitiveType::String,
            'is_big' => EdmPrimitiveType::Boolean,
        ];
    }

    public function query(CustomQueryOptions $options): Builder
    {
        return DB::table('filter_items');
    }
}

final class FilterService extends ODataService
{
    public function __construct()
    {
        parent::__construct(serviceUriValue: '', namespaceValue: 'Test.Ns');
    }

    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        $this->discoverModel(FilterItem::class);
        $this->discoverModel(FilterPart::class);
        $this->discoverCustomEntitySet(FilterItemsSql::class);

        return $builder->namespace($this->namespace());
    }
}

beforeEach(function () {
    $this->withExceptionHandling();

    DB::statement('CREATE TABLE filter_items (id integer primary key, label varchar(20), origin varchar(3), is_big boolean)');
    DB::statement('CREATE TABLE filter_parts (id integer primary key, item_id integer, name varchar(20))');
    DB::table('filter_items')->insert([
        ['id' => 1, 'label' => '50% off', 'origin' => 'LHR', 'is_big' => 1],
        ['id' => 2, 'label' => '500 units', 'origin' => 'lax', 'is_big' => 0],
        ['id' => 3, 'label' => 'a_b', 'origin' => 'LHR', 'is_big' => 0],
        ['id' => 4, 'label' => 'axb', 'origin' => 'jfk', 'is_big' => null],
    ]);
    DB::table('filter_parts')->insert([['id' => 1, 'item_id' => 1, 'name' => 'p1'], ['id' => 2, 'item_id' => 3, 'name' => 'p2']]);

    $service = new FilterService();
    $this->app->instance(ODataServiceRegistryInterface::class, new class ($service) implements ODataServiceRegistryInterface {
        public function __construct(private readonly ODataServiceInterface $service) {}
        public function resolve(string $fullPath): ODataServiceInterface { return $this->service; }
        public function services(): array { return [$this->service]; }
    });
});

/** @return array{int, list<int>|string} status, then ids or the error code */
function filterResult(object $test, string $set, string $filter): array
{
    $response = $test->get("/odata/{$set}?\$orderby=id&\$filter=" . rawurlencode($filter));
    $body     = $response->baseResponse instanceof Symfony\Component\HttpFoundation\StreamedResponse
        ? $response->streamedContent()
        : $response->getContent();
    $data     = json_decode($body, true);

    return [$response->getStatusCode(), isset($data['value']) ? array_column($data['value'], 'id') : $data['error']['code']];
}

foreach (['Items' => 'Eloquent', 'SqlItems' => 'SQL'] as $set => $path) {
    describe("\$filter on the {$path} path", function () use ($set) {
        it('translates what it supports', function (string $filter, array $ids) use ($set) {
            expect(filterResult($this, $set, $filter))->toBe([200, $ids]);
        })->with([
            'eq'                     => ["origin eq 'lax'", [2]],
            'in'                     => ['id in (1,3)', [1, 3]],
            'in, spaced'             => ["origin in ( 'lax' , 'jfk' )", [2, 4]],
            'parens, spaced'         => ['( id eq 1 ) or ( id eq 2 )', [1, 2]],
            'function, spaced'       => ["contains( label , 'a_b' )", [3]],
            'mirrored'               => ['2 lt id', [3, 4]],
            'eq null'                => ['is_big eq null', [4]],
            'ne null'                => ['is_big ne null', [1, 2, 3]],
            'boolean property'       => ['is_big', [1]],
            'false'                  => ['false', []],
            'not'                    => ['not (id eq 1)', [2, 3, 4]],
            'tolower eq'             => ["tolower(origin) eq 'lhr'", [1, 3]],
            'tolower both sides'     => ["tolower(origin) eq tolower('LHR')", [1, 3]],
            'toupper eq'             => ["toupper(origin) eq 'LAX'", [2]],
            'contains % literally'   => ["contains(label,'50%')", [1]],
            'contains _ literally'   => ["contains(label,'a_b')", [3]],
            'startswith'             => ["startswith(label,'50')", [1, 2]],
            'contains with tolower'  => ["contains(tolower(origin),'lh')", [1, 3]],
        ]);

        it('refuses what it cannot translate', function (string $filter, int $status, string $code) use ($set) {
            expect(filterResult($this, $set, $filter))->toBe([$status, $code]);
        })->with([
            'function as operand'  => ['length(origin) eq 3', 501, 'unsupported_filter'],
            'concat'               => ["concat(origin,'x') eq 'LHRx'", 501, 'unsupported_filter'],
            'arithmetic'           => ['id add 1 eq 2', 501, 'unsupported_filter'],
            'mod'                  => ['id mod 2 eq 0', 501, 'unsupported_filter'],
            'gt null'              => ['id gt null', 400, 'invalid_filter'],
            'no property'          => ['1 eq 1', 400, 'invalid_filter'],
        ]);
    });
}

it('translates any() and all() over HTTP on the Eloquent path', function (string $filter, array $ids) {
    expect(filterResult($this, 'Items', $filter))->toBe([200, $ids]);
})->with([
    'any'         => ["parts/any(p:p/name eq 'p2')", [3]],
    'any, spaced' => ["parts/any(p: p/name eq 'p2')", [3]],
    'all'         => ["parts/all(p: p/name eq 'p1')", [1, 2, 4]],   // no parts satisfies all()
]);

// The translators driven directly, independent of the parser.

function filterIds(Illuminate\Database\Query\Builder|Illuminate\Database\Eloquent\Builder $query): array
{
    return $query->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
}

it('translates in on both paths', function () {
    $in = new LaravelUi5\OData\Protocol\Planning\Expression\BinaryExpression(
        new LaravelUi5\OData\Protocol\Planning\Expression\PropertyPathExpression([new LaravelUi5\OData\Edm\Property\Property('id', new LaravelUi5\OData\Edm\Type\PrimitiveType(EdmPrimitiveType::Int32))]),
        LaravelUi5\OData\Protocol\Planning\Expression\BinaryOperator::In,
        new LaravelUi5\OData\Protocol\Planning\Expression\FunctionCallExpression('__list', [
            new LaravelUi5\OData\Protocol\Planning\Expression\LiteralExpression(1, 'Edm.Int32'),
            new LaravelUi5\OData\Protocol\Planning\Expression\LiteralExpression(3, 'Edm.Int32'),
        ]),
    );

    $sql = DB::table('filter_items');
    (new LaravelUi5\OData\Driver\Sql\Expression\FilterToQuery($sql))->apply($in);
    $eloquent = FilterItem::query();
    (new LaravelUi5\OData\Driver\Sql\Expression\FilterToEloquent($eloquent))->apply($in);

    expect(filterIds($sql))->toBe([1, 3])
        ->and(filterIds($eloquent))->toBe([1, 3]);
});

it('translates any() on the Eloquent path and refuses it on the SQL path', function () {
    $name   = new LaravelUi5\OData\Edm\Property\Property('name', new LaravelUi5\OData\Edm\Type\PrimitiveType(EdmPrimitiveType::String));
    $parts  = new LaravelUi5\OData\Edm\Property\NavigationProperty('parts', new LaravelUi5\OData\Edm\Type\EntityType(namespace: 'T', name: 'Part'));
    $lambda = new LaravelUi5\OData\Protocol\Planning\Expression\LambdaExpression(
        new LaravelUi5\OData\Protocol\Planning\Expression\PropertyPathExpression([$parts]),
        'p',
        new LaravelUi5\OData\Protocol\Planning\Expression\BinaryExpression(
            new LaravelUi5\OData\Protocol\Planning\Expression\PropertyPathExpression([$name]),
            LaravelUi5\OData\Protocol\Planning\Expression\BinaryOperator::Eq,
            new LaravelUi5\OData\Protocol\Planning\Expression\LiteralExpression('p2', 'Edm.String'),
        ),
        LaravelUi5\OData\Protocol\Planning\Expression\LambdaOperator::Any,
    );

    $eloquent = FilterItem::query();
    (new LaravelUi5\OData\Driver\Sql\Expression\FilterToEloquent($eloquent))->apply($lambda);

    expect(filterIds($eloquent))->toBe([3])
        ->and(fn () => (new LaravelUi5\OData\Driver\Sql\Expression\FilterToQuery(DB::table('filter_items')))->apply($lambda))
        ->toThrow(LaravelUi5\OData\Exception\NotImplementedException::class);
});
