<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Fixtures\Models\AnnotatedAirport;
use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataEntity;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataIgnore;
use LaravelUi5\OData\Tests\TestCase;

/*
 * OP24, decided 2026-10-07: column-level discovery attributes sit on a PHP 8.4
 * property with hooks that delegate to Eloquent's attribute bag, and
 * #[ODataEntity(useHidden: true)] keeps the model's $hidden out of the schema.
 *
 * The first block checks the idiom against what a model must keep doing;
 * the second the useHidden switch.
 */

uses(TestCase::class);

// ── The hook idiom ───────────────────────────────────────────────────────────

/** The trap the hooks avoid: a plain declared property shadows the attribute bag. */
class ShadowingAirport extends Model
{
    protected $table = 'airports';
    public $timestamps = false;
    protected $guarded = [];

    #[ODataIgnore]
    public $code;
}

describe('column attributes on hooked properties', function () {
    it('keeps mass assignment, reads and toArray() on the attribute bag', function () {
        $airport = AnnotatedAirport::create(['code' => 'VIE', 'name' => 'Vienna']);

        expect($airport->code)->toBe('VIE')
            ->and($airport->getAttribute('code'))->toBe('VIE')
            ->and($airport->toArray())->toMatchArray(['code' => 'VIE', 'name' => 'Vienna'])
            ->and(isset($airport->code))->toBeTrue()
            ->and(isset($airport->country_id))->toBeFalse();   // null column → not set, as on a plain model
    });

    it('writes through the hook, tracks dirtiness and persists on save()', function () {
        $airport = AnnotatedAirport::create(['code' => 'VIE', 'name' => 'Vienna']);

        $airport->name = 'Wien';
        expect($airport->isDirty('name'))->toBeTrue();
        $airport->save();

        expect(AnnotatedAirport::find($airport->id)->name)->toBe('Wien');
    });

    it('fills through fill() and update() like any model', function () {
        $airport = AnnotatedAirport::create(['code' => 'VIE', 'name' => 'Vienna']);
        $airport->update(['code' => 'LOWW']);

        expect(AnnotatedAirport::find($airport->id)->code)->toBe('LOWW');
    });

    it('shows the trap: a plain declared property drops writes on save()', function () {
        $id = DB::table('airports')->insertGetId(['code' => 'VIE', 'name' => 'Vienna']);

        $airport = ShadowingAirport::find($id);
        expect($airport->code)->toBeNull();          // the column value never reaches the property

        $airport->code = 'LOWW';
        $airport->save();
        expect(DB::table('airports')->where('id', $id)->value('code'))->toBe('VIE');   // write lost
    });
});

// ── useHidden ────────────────────────────────────────────────────────────────

#[ODataEntity(name: 'Account', entitySet: 'Accounts', useHidden: true)]
class HiddenAccount extends Model
{
    protected $table = 'hidden_accounts';
    public $timestamps = false;
    protected $guarded = [];
    protected $hidden = ['password', 'tokens', 'id'];   // `id` is the key: it stays

    public function tokens(): HasMany
    {
        return $this->hasMany(HiddenToken::class, 'account_id');
    }
}

#[ODataEntity(name: 'OpenAccount', entitySet: 'OpenAccounts')]
class OpenAccount extends Model
{
    protected $table = 'hidden_accounts';
    public $timestamps = false;
    protected $guarded = [];
    protected $hidden = ['password'];
}

#[ODataEntity(name: 'Token', entitySet: 'Tokens')]
class HiddenToken extends Model
{
    protected $table = 'hidden_tokens';
    public $timestamps = false;
    protected $guarded = [];
}

final class HiddenService extends ODataService
{
    public function __construct()
    {
        parent::__construct(serviceUriValue: '', namespaceValue: 'Test.Ns');
    }

    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        $this->discoverModel(HiddenAccount::class);
        $this->discoverModel(OpenAccount::class);
        $this->discoverModel(HiddenToken::class);

        return $builder->namespace($this->namespace());
    }
}

describe('#[ODataEntity(useHidden: true)]', function () {
    beforeEach(function () {
        $this->withExceptionHandling();

        DB::statement('CREATE TABLE hidden_accounts (id integer primary key, name varchar(40) not null, password varchar(60) not null)');
        DB::statement('CREATE TABLE hidden_tokens (id integer primary key, account_id integer not null)');
        DB::table('hidden_accounts')->insert(['id' => 1, 'name' => 'alice', 'password' => '$2y$10$secret']);

        $service = new HiddenService();
        $this->app->instance(ODataServiceRegistryInterface::class, new class ($service) implements ODataServiceRegistryInterface {
            public function __construct(private readonly ODataServiceInterface $service) {}
            public function resolve(string $fullPath): ODataServiceInterface { return $this->service; }
            public function services(): array { return [$this->service]; }
        });
    });

    it('declares neither hidden columns nor hidden relations, but keeps the key', function () {
        $xml     = $this->get('/odata/$metadata')->streamedContent();
        $account = substr($xml, strpos($xml, '<EntityType Name="Account">'));
        $account = substr($account, 0, strpos($account, '</EntityType>'));

        expect($account)
            ->toContain('<PropertyRef Name="id"/>')
            ->toContain('Name="name"')
            ->not->toContain('Name="password"')
            ->not->toContain('NavigationProperty Name="tokens"');
    });

    it('refuses a $filter on a hidden column', function () {
        $this->get("/odata/Accounts?\$filter=startswith(password,'\$2y')")->assertStatus(400);
    });

    it('leaves a model without the switch as it was', function () {
        expect($this->get('/odata/$metadata')->streamedContent())
            ->toMatch('/<EntityType Name="OpenAccount">.*Name="password"/s');
    });
});
