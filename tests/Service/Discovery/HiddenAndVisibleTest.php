<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataEntity;
use LaravelUi5\OData\Tests\TestCase;

/*
 * OP41, decided 2026-11-02: what the model never serializes ($hidden, $visible) is not declared — always, no switch.
 * The hook idiom that used to be checked here went with the property attributes (OP38).
 */

uses(TestCase::class);

// ── $hidden and $visible (OP41) ──────────────────────────────────────────────

#[ODataEntity(name: 'Account', entitySet: 'Accounts')]
class HiddenAccount extends Model
{
    protected $table = 'hidden_accounts';
    public $timestamps = false;
    protected $guarded = [];
    protected $hidden = ['password', 'tokens'];

    public function tokens(): HasMany
    {
        return $this->hasMany(HiddenToken::class, 'account_id');
    }
}

#[ODataEntity(name: 'Profile', entitySet: 'Profiles')]
class VisibleAccount extends Model
{
    protected $table = 'hidden_accounts';
    public $timestamps = false;
    protected $guarded = [];
    protected $visible = ['id', 'name', 'password'];
    protected $hidden = ['password'];   // $hidden removes from $visible, as in toArray()

    public function tokens(): HasMany
    {
        return $this->hasMany(HiddenToken::class, 'account_id');
    }
}

#[ODataEntity(name: 'Token', entitySet: 'Tokens')]
class HiddenToken extends Model
{
    protected $table = 'hidden_tokens';
    public $timestamps = false;
    protected $guarded = [];
}

#[ODataEntity(name: 'Locked', entitySet: 'Locked')]
class HiddenKeyAccount extends Model
{
    protected $table = 'hidden_accounts';
    public $timestamps = false;
    protected $hidden = ['id'];
}

#[ODataEntity(name: 'Unkeyed', entitySet: 'Unkeyed')]
class InvisibleKeyAccount extends Model
{
    protected $table = 'hidden_accounts';
    public $timestamps = false;
    protected $visible = ['name'];
}

/** Names its type like HiddenToken does: collides. */
#[ODataEntity(name: 'Token', entitySet: 'MoreTokens')]
class OtherToken extends Model
{
    protected $table = 'hidden_tokens';
    public $timestamps = false;
}

final class HiddenService extends ODataService
{
    /** @var list<class-string<Model>> */
    public static array $models = [HiddenAccount::class, VisibleAccount::class, HiddenToken::class];

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

function entityTypeXml(string $xml, string $name): string
{
    $type = substr($xml, strpos($xml, '<EntityType Name="' . $name . '">'));

    return substr($type, 0, strpos($type, '</EntityType>'));
}

describe('what the model never serializes is not declared', function () {
    beforeEach(function () {
        $this->withExceptionHandling();
        HiddenService::$models = [HiddenAccount::class, VisibleAccount::class, HiddenToken::class];

        DB::statement('CREATE TABLE hidden_accounts (id integer primary key, name varchar(40) not null, password varchar(60) not null, note varchar(40))');
        DB::statement('CREATE TABLE hidden_tokens (id integer primary key, account_id integer not null)');
        DB::table('hidden_accounts')->insert(['id' => 1, 'name' => 'alice', 'password' => '$2y$10$secret', 'note' => 'n']);

        $service = new HiddenService();
        $this->app->instance(ODataServiceRegistryInterface::class, new class ($service) implements ODataServiceRegistryInterface {
            public function __construct(private readonly ODataServiceInterface $service) {}
            public function resolve(string $fullPath): ODataServiceInterface { return $this->service; }
            public function services(): array { return [$this->service]; }
        });
    });

    it('leaves $hidden columns and relations out, without a switch', function () {
        expect(entityTypeXml($this->get('/odata/$metadata')->streamedContent(), 'Account'))
            ->toContain('<PropertyRef Name="id"/>')
            ->toContain('Name="name"')
            ->toContain('Name="note"')
            ->not->toContain('Name="password"')
            ->not->toContain('NavigationProperty Name="tokens"');
    });

    it('declares only what $visible admits, less $hidden', function () {
        expect(entityTypeXml($this->get('/odata/$metadata')->streamedContent(), 'Profile'))
            ->toContain('Name="id"')
            ->toContain('Name="name"')
            ->not->toContain('Name="password"')
            ->not->toContain('Name="note"')
            ->not->toContain('NavigationProperty Name="tokens"');
    });

    it('refuses a $filter on a hidden column', function () {
        $this->get("/odata/Accounts?\$filter=startswith(password,'\$2y')")->assertStatus(400);
    });

    it('declares exactly what goes on the wire', function () {
        $row = json_decode($this->get('/odata/Profiles(1)')->streamedContent(), true);

        expect(array_keys(array_filter($row, fn (string $k) => !str_starts_with($k, '@'), ARRAY_FILTER_USE_KEY)))
            ->toBe(['id', 'name']);
    });
});

describe('discovery fails loud', function () {
    beforeEach(function () {
        DB::statement('CREATE TABLE hidden_accounts (id integer primary key, name varchar(40) not null, password varchar(60) not null, note varchar(40))');
        DB::statement('CREATE TABLE hidden_tokens (id integer primary key, account_id integer not null)');
    });

    it('on a key in $hidden', function () {
        HiddenService::$models = [HiddenKeyAccount::class];

        expect(fn () => (new HiddenService())->schema())
            ->toThrow(LogicException::class, 'The key "id" of HiddenKeyAccount is never serialized');
    });

    it('on a key outside $visible', function () {
        HiddenService::$models = [InvisibleKeyAccount::class];

        expect(fn () => (new HiddenService())->schema())
            ->toThrow(LogicException::class, 'The key "id" of InvisibleKeyAccount is never serialized');
    });

    it('on two models with the same entity type name, naming both', function () {
        HiddenService::$models = [HiddenToken::class, OtherToken::class];

        expect(fn () => (new HiddenService())->schema())
            ->toThrow(LogicException::class, 'Both HiddenToken and OtherToken resolve to the entity type "Token"');
    });
});
