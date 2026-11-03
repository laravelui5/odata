<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataEntity;
use LaravelUi5\OData\Tests\TestCase;
use function LaravelUi5\OData\Tests\testCase;

/*
 * OP39 (2026-11-02): discovery declares an array/json/object/collection cast as Edm.String, and the wire carries that
 * string — JSON text — not the decoded value as a JSON object.
 */

uses(TestCase::class);

#[ODataEntity(name: 'Job', entitySet: 'Jobs')]
class JsonJob extends Model
{
    protected $table = 'json_jobs';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = [
        'input'   => 'array',
        'options' => 'object',
        'tags'    => 'collection',
        'extra'   => AsArrayObject::class,
    ];

    public function runs(): HasMany
    {
        return $this->hasMany(JsonRun::class, 'job_id');
    }
}

#[ODataEntity(name: 'Run', entitySet: 'Runs')]
class JsonRun extends Model
{
    protected $table = 'json_runs';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['output' => 'json'];
}

final class JsonService extends ODataService
{
    public function __construct()
    {
        parent::__construct(serviceUriValue: '', namespaceValue: 'Test.Ns');
    }

    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        $this->discoverModel(JsonJob::class);
        $this->discoverModel(JsonRun::class);

        return $builder->namespace($this->namespace());
    }
}

beforeEach(function () {
    $this->withExceptionHandling();

    DB::statement('CREATE TABLE json_jobs (id integer primary key, input text, options text, tags text, extra text, raw json)');
    DB::statement('CREATE TABLE json_runs (id integer primary key, job_id integer not null, output text)');
    DB::table('json_jobs')->insert([
        'id'      => 1,
        'input'   => '{"x":1,"path":"a/b","name":"Zürich"}',
        'options' => '{"dry":true}',
        'tags'    => '["a","b"]',
        'extra'   => '{"k":"v"}',
        'raw'     => '{"untouched":true}',
    ]);
    DB::table('json_jobs')->insert(['id' => 2, 'input' => null, 'options' => null, 'tags' => null, 'extra' => null, 'raw' => null]);
    DB::table('json_runs')->insert(['id' => 10, 'job_id' => 1, 'output' => '[]']);

    $service = new JsonService();
    $this->app->instance(ODataServiceRegistryInterface::class, new class ($service) implements ODataServiceRegistryInterface {
        public function __construct(private readonly ODataServiceInterface $service) {}
        public function resolve(string $fullPath): ODataServiceInterface { return $this->service; }
        public function services(): array { return [$this->service]; }
    });
});

function jsonGet(string $url): array
{
    $response = testCase()->get($url);
    $response->assertStatus(200);

    return json_decode($response->streamedContent(), true);
}

it('declares the casts as Edm.String', function () {
    $xml = testCase()->get('/odata/$metadata')->streamedContent();

    foreach (['input', 'options', 'tags', 'extra', 'raw'] as $name) {
        expect($xml)->toContain('<Property Name="' . $name . '" Type="Edm.String"');
    }
});

it('writes every structured cast as JSON text', function () {
    $job = jsonGet('/odata/Jobs(1)');

    expect($job['input'])->toBe('{"x":1,"path":"a/b","name":"Zürich"}')
        ->and($job['options'])->toBe('{"dry":true}')
        ->and($job['tags'])->toBe('["a","b"]')
        ->and($job['extra'])->toBe('{"k":"v"}')
        ->and($job['raw'])->toBe('{"untouched":true}');   // no cast: the column's text, as before
});

it('keeps null as null', function () {
    $job = jsonGet('/odata/Jobs(2)');

    expect($job['input'])->toBeNull()
        ->and($job['options'])->toBeNull()
        ->and($job['extra'])->toBeNull();
});

it('writes an empty array as "[]" in an expanded row and in a collection', function () {
    expect(jsonGet('/odata/Jobs(1)?$expand=runs')['runs'][0]['output'])->toBe('[]')
        ->and(jsonGet('/odata/Runs')['value'][0]['output'])->toBe('[]');
});
