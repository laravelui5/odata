<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataEntity;
use LaravelUi5\OData\Tests\TestCase;

/*
 * OP23: Edm.Binary goes out base64url-encoded without padding (OData JSON
 * format). Raw bytes are rarely valid UTF-8 — before, json_encode failed and the
 * response did not come out at all. /$value still answers the raw bytes.
 */

uses(TestCase::class);

#[ODataEntity(name: 'Attachment', entitySet: 'Attachments')]
class BinaryAttachment extends Model
{
    protected $table = 'binary_attachments';
    public $timestamps = false;
    protected $guarded = [];
}

final class BinaryService extends ODataService
{
    public function __construct()
    {
        parent::__construct(serviceUriValue: '', namespaceValue: 'Test.Ns');
    }

    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        $this->discoverModel(BinaryAttachment::class);

        return $builder->namespace($this->namespace());
    }
}

const BINARY_BYTES = "\xff\xfe\x00\x01\xfb";   // not UTF-8; base64 "//4AAfs=" → base64url "__4AAfs"

beforeEach(function () {
    $this->withExceptionHandling();

    DB::statement('CREATE TABLE binary_attachments (id integer primary key, data blob null)');
    DB::table('binary_attachments')->insert([['id' => 1, 'data' => BINARY_BYTES], ['id' => 2, 'data' => null]]);

    $service = new BinaryService();
    $this->app->instance(ODataServiceRegistryInterface::class, new class ($service) implements ODataServiceRegistryInterface {
        public function __construct(private readonly ODataServiceInterface $service) {}
        public function resolve(string $fullPath): ODataServiceInterface { return $this->service; }
        public function services(): array { return [$this->service]; }
    });
});

it('declares the column as Edm.Binary', function () {
    expect($this->get('/odata/$metadata')->streamedContent())->toContain('<Property Name="data" Type="Edm.Binary"');
});

it('writes binary values as base64url without padding', function () {
    $rows = json_decode($this->get('/odata/Attachments?$orderby=id')->streamedContent(), true)['value'];

    expect($rows)->toBe([
        ['id' => 1, 'data' => '__4AAfs'],
        ['id' => 2, 'data' => null],
    ]);
});

it('encodes binary in a single entity and a property value', function () {
    expect(json_decode($this->get('/odata/Attachments(1)')->streamedContent(), true)['data'])->toBe('__4AAfs')
        ->and(json_decode($this->get('/odata/Attachments(1)/data')->streamedContent(), true)['value'])->toBe('__4AAfs');
});

it('answers /$value with the raw bytes', function () {
    $response = $this->get('/odata/Attachments(1)/data/$value');

    expect($response->streamedContent())->toBe(BINARY_BYTES)
        ->and($response->headers->get('Content-Type'))->toBe('application/octet-stream');
});
