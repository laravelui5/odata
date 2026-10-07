<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Protocol\Execution\RowCoercion;
use LaravelUi5\OData\Protocol\Execution\WireFormat;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataEntity;
use LaravelUi5\OData\Tests\TestCase;

/*
 * OP27: `IEEE754Compatible=true` in the Accept header (UI5's V4 model always sends
 * it, per request and per $batch part) asks for Edm.Int64 and Edm.Decimal as
 * JSON strings. Without it they stay numbers.
 */

uses(TestCase::class);

#[ODataEntity(name: 'Order', entitySet: 'Orders')]
class Ieee754Order extends Model
{
    protected $table = 'ieee_orders';
    public $timestamps = false;
    protected $guarded = [];

    public function lines(): HasMany
    {
        return $this->hasMany(Ieee754Line::class, 'order_id');
    }
}

#[ODataEntity(name: 'Line', entitySet: 'Lines')]
class Ieee754Line extends Model
{
    protected $table = 'ieee_lines';
    public $timestamps = false;
    protected $guarded = [];
}

final class Ieee754Service extends ODataService
{
    public function __construct()
    {
        parent::__construct(serviceUriValue: '', namespaceValue: 'Test.Ns');
    }

    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        $this->discoverModel(Ieee754Order::class);
        $this->discoverModel(Ieee754Line::class);

        return $builder->namespace($this->namespace());
    }
}

const IEEE754_ACCEPT = 'application/json;odata.metadata=minimal;IEEE754Compatible=true';

beforeEach(function () {
    $this->withExceptionHandling();

    DB::statement('CREATE TABLE ieee_orders (id integer primary key, big bigint not null, amount decimal(19,6) not null, label varchar(20) not null)');
    DB::statement('CREATE TABLE ieee_lines (id integer primary key, order_id integer not null, amount decimal(19,6) not null)');
    // 2^53 + 1: a JavaScript number cannot hold it.
    DB::table('ieee_orders')->insert(['id' => 1, 'big' => 9007199254740993, 'amount' => 1234.5, 'label' => 'first']);
    DB::table('ieee_lines')->insert([['id' => 10, 'order_id' => 1, 'amount' => 0.25], ['id' => 11, 'order_id' => 1, 'amount' => 3]]);

    $service = new Ieee754Service();
    $this->app->instance(ODataServiceRegistryInterface::class, new class ($service) implements ODataServiceRegistryInterface {
        public function __construct(private readonly ODataServiceInterface $service) {}
        public function resolve(string $fullPath): ODataServiceInterface { return $this->service; }
        public function services(): array { return [$this->service]; }
    });
});

function ieee754Json(Illuminate\Testing\TestResponse $response): array
{
    return json_decode($response->streamedContent(), true, flags: JSON_BIGINT_AS_STRING);
}

describe('IEEE754Compatible on direct requests', function () {
    it('keeps numbers as numbers without the parameter', function () {
        $response = $this->get('/odata/Orders');
        $row      = ieee754Json($response)['value'][0];

        expect($row['amount'])->toBe(1234.5)
            ->and($response->headers->get('Content-Type'))->not->toContain('IEEE754Compatible');
    });

    it('writes Decimal and Int64 as strings with the parameter, and says so', function () {
        $response = $this->get('/odata/Orders', ['Accept' => IEEE754_ACCEPT]);
        $row      = ieee754Json($response)['value'][0];

        expect($row['amount'])->toBe('1234.5')
            ->and($row['big'])->toBe('9007199254740993')
            ->and($row['id'])->toBe(1)          // Int32 stays a number
            ->and($row['label'])->toBe('first')
            ->and($response->headers->get('Content-Type'))->toContain('IEEE754Compatible=true');
    });

    it('applies to expanded rows', function () {
        $row = ieee754Json($this->get('/odata/Orders?$expand=lines', ['Accept' => IEEE754_ACCEPT]))['value'][0];

        expect(array_column($row['lines'], 'amount'))->toBe(['0.25', '3']);
    });

    it('applies to a single entity and to a property value', function () {
        $entity   = ieee754Json($this->get('/odata/Orders(1)', ['Accept' => IEEE754_ACCEPT]));
        $property = ieee754Json($this->get('/odata/Orders(1)/amount', ['Accept' => IEEE754_ACCEPT]));

        expect($entity['big'])->toBe('9007199254740993')
            ->and($property['value'])->toBe('1234.5');
    });
});

describe('IEEE754Compatible inside $batch', function () {
    it('reads the Accept header of each multipart part', function () {
        $body = "--batch_1\r\n"
            . "Content-Type: application/http\r\n"
            . "Content-Transfer-Encoding: binary\r\n\r\n"
            . "GET Orders HTTP/1.1\r\n"
            . 'Accept: ' . IEEE754_ACCEPT . "\r\n\r\n\r\n"
            . "--batch_1\r\n"
            . "Content-Type: application/http\r\n"
            . "Content-Transfer-Encoding: binary\r\n\r\n"
            . "GET Lines HTTP/1.1\r\n"
            . "Accept: application/json\r\n\r\n\r\n"
            . "--batch_1--\r\n";

        $content = $this->call('POST', '/odata/$batch', [], [], [], [
            'CONTENT_TYPE' => 'multipart/mixed; boundary=batch_1',
            'HTTP_ACCEPT'  => 'multipart/mixed',
        ], $body)->streamedContent();

        expect($content)
            ->toContain('Content-Type: application/json;odata.metadata=minimal;IEEE754Compatible=true;charset=utf-8')
            ->toContain('"amount":"1234.5"')
            ->toContain('"amount":0.25');        // the second part asked for nothing
    });

    it('reads the headers object of a JSON batch request', function () {
        $data = json_decode($this->postJson('/odata/$batch', ['requests' => [
            ['id' => 'a', 'method' => 'GET', 'url' => 'Orders', 'headers' => ['accept' => IEEE754_ACCEPT]],
        ]])->streamedContent(), true);

        expect($data['responses'][0]['body']['value'][0]['amount'])->toBe('1234.5')
            ->and($data['responses'][0]['headers']['content-type'])->toContain('IEEE754Compatible=true');
    });
});

describe('RowCoercion number formatting', function () {
    $type = function (): EntityType {
        $id = new Property('id', new PrimitiveType(EdmPrimitiveType::Int32));

        return new EntityType(namespace: 'T', name: 'N', key: [$id], declaredProperties: [
            $id,
            new Property('d', new PrimitiveType(EdmPrimitiveType::Decimal)),
        ]);
    };

    it('writes decimals as exact strings, never in exponent notation', function () use ($type) {
        $coercion = new RowCoercion($type(), new WireFormat(true));

        expect($coercion->apply(['d' => '1234567890123.123456'])['d'])->toBe('1234567890123.123456') // driver string: untouched
            ->and($coercion->apply(['d' => 0.1])['d'])->toBe('0.1')
            ->and($coercion->apply(['d' => 1.0E-7])['d'])->toBe('0.0000001')
            ->and($coercion->apply(['d' => 1.5E21])['d'])->toBe('1500000000000000000000')
            ->and($coercion->apply(['d' => 42])['d'])->toBe('42')
            ->and($coercion->apply(['d' => null])['d'])->toBeNull();
    });

    it('detects the parameter in an Accept header', function () {
        expect(WireFormat::fromAccept(IEEE754_ACCEPT)->ieee754Compatible)->toBeTrue()
            ->and(WireFormat::fromAccept('application/json;IEEE754Compatible=false')->ieee754Compatible)->toBeFalse()
            ->and(WireFormat::fromAccept(null)->ieee754Compatible)->toBeFalse();
    });
});
