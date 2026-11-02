<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Protocol\Planning\ExpandItem;
use LaravelUi5\OData\Service\Contracts\CustomEntitySetInterface;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Service\Contracts\QueryPlanInterface;
use LaravelUi5\OData\Service\Contracts\VirtualExpandResolverInterface;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataEntity;
use LaravelUi5\OData\Tests\TestCase;

/*
 * OP36, widened to a rule (2026-11-02): the Eloquent path emits exactly the declared properties, at every level.
 * Whatever else toArray() carries stays behind — $appends, a belongsToMany row's pivot, a relation loaded through
 * $with but not expanded. A virtual expand resolver still receives the full parent row.
 */

uses(TestCase::class);

#[ODataEntity(name: 'Shelf', entitySet: 'Shelves')]
class WireShelf extends Model
{
    protected $table = 'wire_shelves';
    public $timestamps = false;
    protected $guarded = [];
    protected $appends = ['shouting'];
    protected $with = ['books'];   // loaded on every query, expanded only on request

    protected function shouting(): Attribute
    {
        return Attribute::get(fn () => strtoupper((string) $this->getAttribute('label')));
    }

    public function books(): HasMany
    {
        return $this->hasMany(WireBook::class, 'shelf_id');
    }
}

#[ODataEntity(name: 'Book', entitySet: 'Books')]
class WireBook extends Model
{
    protected $table = 'wire_books';
    public $timestamps = false;
    protected $guarded = [];
    protected $appends = ['slug'];

    protected function slug(): Attribute
    {
        return Attribute::get(fn () => strtolower(str_replace(' ', '-', (string) $this->getAttribute('title'))));
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(WireTag::class, 'wire_book_tag', 'book_id', 'tag_id')->withPivot('weight');
    }
}

#[ODataEntity(name: 'Tag', entitySet: 'Tags')]
class WireTag extends Model
{
    protected $table = 'wire_tags';
    public $timestamps = false;
    protected $guarded = [];
}

/** Records what the engine hands a virtual expand resolver. */
final class WireShelfNotes implements CustomEntitySetInterface, VirtualExpandResolverInterface
{
    /** @var list<array{row: array<string, mixed>, type: string}> */
    public static array $calls = [];

    public function entitySetName(): string
    {
        return 'ShelfNotes';
    }

    public function entityType(string $namespace): EntityTypeInterface
    {
        $key = new Property('note_id', new PrimitiveType(EdmPrimitiveType::Int32));

        return new EntityType(
            namespace: $namespace,
            name: 'ShelfNote',
            key: [$key],
            declaredProperties: [$key, new Property('text', new PrimitiveType(EdmPrimitiveType::String))],
        );
    }

    public function expandsOn(): array
    {
        return ['Shelf' => 'notes'];
    }

    public function resolveExpand(array $parentRow, string $parentEntityType, ExpandItem $expand): array
    {
        self::$calls[] = ['row' => $parentRow, 'type' => $parentEntityType];

        return [['note_id' => 1, 'text' => 'dusty']];
    }

    public function resolve(QueryPlanInterface $plan): \Generator
    {
        yield from [];
    }

    public function count(QueryPlanInterface $plan): int
    {
        return 0;
    }
}

final class WireService extends ODataService
{
    public function __construct()
    {
        parent::__construct(serviceUriValue: '', namespaceValue: 'Test.Ns');
    }

    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        $this->discoverModel(WireShelf::class);
        $this->discoverModel(WireBook::class);
        $this->discoverModel(WireTag::class);
        $this->discoverCustomEntitySet(WireShelfNotes::class);

        return $builder->namespace($this->namespace());
    }
}

beforeEach(function () {
    $this->withExceptionHandling();
    WireShelfNotes::$calls = [];

    DB::statement('CREATE TABLE wire_shelves (id integer primary key, label varchar(40) not null)');
    DB::statement('CREATE TABLE wire_books (id integer primary key, shelf_id integer not null, title varchar(40) not null)');
    DB::statement('CREATE TABLE wire_tags (id integer primary key, name varchar(40) not null)');
    DB::statement('CREATE TABLE wire_book_tag (book_id integer not null, tag_id integer not null, weight integer not null)');
    DB::table('wire_shelves')->insert(['id' => 1, 'label' => 'fiction']);
    DB::table('wire_books')->insert(['id' => 10, 'shelf_id' => 1, 'title' => 'Moby Dick']);
    DB::table('wire_tags')->insert(['id' => 100, 'name' => 'whales']);
    DB::table('wire_book_tag')->insert(['book_id' => 10, 'tag_id' => 100, 'weight' => 3]);

    $service = new WireService();
    $this->app->instance(ODataServiceRegistryInterface::class, new class ($service) implements ODataServiceRegistryInterface {
        public function __construct(private readonly ODataServiceInterface $service) {}
        public function resolve(string $fullPath): ODataServiceInterface { return $this->service; }
        public function services(): array { return [$this->service]; }
    });
});

function wireGet(string $url): array
{
    $response = test()->get($url);
    $response->assertStatus(200);

    return json_decode($response->streamedContent(), true);
}

describe('the wire carries exactly the declared properties', function () {
    it('drops $appends and a relation loaded through $with from a collection', function () {
        expect(wireGet('/odata/Shelves')['value'][0])->toBe(['id' => 1, 'label' => 'fiction']);
    });

    it('drops them from a single entity', function () {
        $doc = wireGet('/odata/Shelves(1)');

        expect($doc)->toHaveKeys(['id', 'label'])
            ->not->toHaveKey('shouting')
            ->not->toHaveKey('books');
    });

    it('serializes an expanded row against its own type, at every level', function () {
        $book = wireGet('/odata/Shelves(1)?$expand=books($expand=tags)')['books'][0];

        expect(array_keys($book))->toBe(['id', 'shelf_id', 'title', 'tags'])
            ->and($book['tags'][0])->toBe(['id' => 100, 'name' => 'whales']);   // no pivot, no weight
    });

    it('drops the pivot of an expanded belongsToMany collection', function () {
        $tag = wireGet('/odata/Books(10)?$expand=tags')['tags'][0];

        expect($tag)->toBe(['id' => 100, 'name' => 'whales']);
    });

    it('keeps an expanded count and drops the withCount helper column', function () {
        $doc = wireGet('/odata/Books?$expand=tags($count=true)')['value'][0];

        expect($doc)->toHaveKey('tags@odata.count', 1)
            ->and(array_filter(array_keys($doc), fn (string $k) => str_starts_with($k, '__odata')))->toBe([]);
    });

    it('lets $compute see only declared properties', function () {
        $doc = wireGet('/odata/Shelves?$compute=shouting%20as%20loud')['value'][0];

        expect($doc)->toHaveKey('loud', null)
            ->not->toHaveKey('shouting');
    });
});

describe('a virtual expand resolver', function () {
    it('still receives the full parent row, and the declared type name', function () {
        $doc = wireGet('/odata/Shelves(1)?$expand=notes');

        expect($doc['notes'])->toBe([['note_id' => 1, 'text' => 'dusty']])
            ->and($doc)->not->toHaveKey('shouting')
            ->and(WireShelfNotes::$calls[0]['row'])->toHaveKey('shouting', 'FICTION')
            ->and(WireShelfNotes::$calls[0]['type'])->toBe('Shelf');
    });
});
