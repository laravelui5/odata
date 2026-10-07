<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Driver\Sql\SearchClause;
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
 * OP09: `$search` matches the term literally. `%` and `_` used to act as LIKE
 * wildcards (`50%` found every row starting with 50), and the quote trim ate a
 * trailing apostrophe. Same table, both resolvers.
 */

uses(TestCase::class);

#[ODataEntity(name: 'Item', entitySet: 'Items')]
class SearchItem extends Model
{
    protected $table = 'search_items';
    public $timestamps = false;
}

final readonly class SearchItemsSql extends AbstractEntitySet
{
    public function entitySetName(): string { return 'SqlItems'; }
    public function key(): array { return ['id']; }
    public function columns(): array { return ['id' => EdmPrimitiveType::Int32, 'label' => EdmPrimitiveType::String]; }

    public function query(CustomQueryOptions $options): Builder
    {
        return DB::table('search_items');
    }
}

final class SearchService extends ODataService
{
    public function __construct()
    {
        parent::__construct(serviceUriValue: '', namespaceValue: 'Test.Ns');
    }

    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        $this->discoverModel(SearchItem::class);
        $this->discoverCustomEntitySet(SearchItemsSql::class);

        return $builder->namespace($this->namespace());
    }
}

beforeEach(function () {
    $this->withExceptionHandling();

    DB::statement('CREATE TABLE search_items (id integer primary key, label varchar(40) not null)');
    foreach (['50% off', '500 units', 'a_b', 'axb', 'yes!', 'rolls\'', 'rolls'] as $i => $label) {
        DB::table('search_items')->insert(['id' => $i + 1, 'label' => $label]);
    }

    $service = new SearchService();
    $this->app->instance(ODataServiceRegistryInterface::class, new class ($service) implements ODataServiceRegistryInterface {
        public function __construct(private readonly ODataServiceInterface $service) {}
        public function resolve(string $fullPath): ODataServiceInterface { return $this->service; }
        public function services(): array { return [$this->service]; }
    });
});

function searchLabels(object $test, string $set, string $term): array
{
    $data = json_decode($test->get('/odata/' . $set . '?$orderby=id&$search=' . rawurlencode($term))->streamedContent(), true);

    return array_column($data['value'], 'label');
}

foreach (['Items' => 'Eloquent', 'SqlItems' => 'SQL'] as $set => $path) {
    describe("\$search on the {$path} path", function () use ($set) {
        it('matches % literally', fn () => expect(searchLabels($this, $set, '50%'))->toBe(['50% off']));
        it('matches _ literally', fn () => expect(searchLabels($this, $set, 'a_b'))->toBe(['a_b']));
        it('matches the escape character itself', fn () => expect(searchLabels($this, $set, 'yes!'))->toBe(['yes!']));
        it('strips one surrounding pair of quotes', fn () => expect(searchLabels($this, $set, '"axb"'))->toBe(['axb']));
        it('keeps a trailing apostrophe', fn () => expect(searchLabels($this, $set, "rolls'"))->toBe(["rolls'"]));
    });
}

describe('SearchClause helpers', function () {
    it('removes only a matching surrounding pair', function () {
        expect(SearchClause::term('"foo"'))->toBe('foo')
            ->and(SearchClause::term("'foo'"))->toBe('foo')
            ->and(SearchClause::term("\"'foo'\""))->toBe("'foo'")
            ->and(SearchClause::term("foo'"))->toBe("foo'")
            ->and(SearchClause::term('"'))->toBe('"');
    });

    it('escapes the wildcards and the escape character', function () {
        expect(SearchClause::escape('a%b_c!d'))->toBe('a!%b!_c!!d');
    });
});
