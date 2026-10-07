<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Service\Discovery\Attributes\ODataEntity;
use LaravelUi5\OData\Service\Serialization\CsdlSerializer;
use LaravelUi5\OData\Tests\TestCase;
use LaravelUi5\OData\Vocabularies\CodeList\V1\CurrencyCodes;
use LaravelUi5\OData\Vocabularies\CodeList\V1\StandardCode;
use LaravelUi5\OData\Vocabularies\CodeList\V1\UnitsOfMeasure;
use LaravelUi5\OData\Vocabularies\Common\V1\Text;
use LaravelUi5\OData\Vocabularies\Common\V1\UnitSpecificScale;
use LaravelUi5\OData\Vocabularies\Measures\V1\ISOCurrency;
use LaravelUi5\OData\Vocabularies\Measures\V1\Unit;

/*
 * The code-list wiring UI5's Currency / Unit types read per row (OP19 level 3):
 * container annotations pointing at a code-list set, Path-valued Measures
 * annotations on the business properties, a code-list set with one key carrying
 * UnitSpecificScale / Text / StandardCode as paths — and that set answers UI5's
 * request (`$select`, no `$top`, no `Prefer`) unpaged.
 *
 * Shapes taken from the 2026-10-07 probe against OpenUI5 1.136.18.
 */

uses(TestCase::class);

/** A code-list set: one key, the key annotated with paths to scale, text and standard code. */
#[ODataEntity(name: 'Currency', entitySet: 'Currencies')]
class CodeListCurrency extends Model
{
    protected $table = 'code_list_currencies';
    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    #[UnitSpecificScale(EdmPrimitiveType::Int16, new Path('decimals'))]
    #[Text(new Path('name'))]
    #[StandardCode(new Path('iso'))]
    public ?string $code {
        get => $this->getAttribute('code');
        set(?string $value) { $this->setAttribute('code', $value); }
    }
}

/** A business entity: amount and quantity point at their currency and unit. */
#[ODataEntity(name: 'Order', entitySet: 'Orders')]
class CodeListOrder extends Model
{
    protected $table = 'code_list_orders';
    public $timestamps = false;
    protected $guarded = [];

    #[ISOCurrency(new Path('currency'))]
    public ?string $amount {
        get => $this->getAttribute('amount');
        set(?string $value) { $this->setAttribute('amount', $value); }
    }

    #[Unit(new Path('unit'))]
    public ?string $quantity {
        get => $this->getAttribute('quantity');
        set(?string $value) { $this->setAttribute('quantity', $value); }
    }
}

final class CodeListService extends ODataService
{
    public function __construct()
    {
        parent::__construct(serviceUriValue: '', namespaceValue: 'Test.Ns');
    }

    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        $this->discoverModel(CodeListCurrency::class);
        $this->discoverModel(CodeListOrder::class);
        $this->annotateContainer(
            new CurrencyCodes(url: '../codelists@1.0.0/$metadata', collectionPath: 'Currencies'),
            new UnitsOfMeasure(url: '../codelists@1.0.0/$metadata', collectionPath: 'Units'),
        );

        return $builder->namespace($this->namespace());
    }
}

beforeEach(function () {
    $this->withExceptionHandling();

    DB::statement('CREATE TABLE code_list_currencies (code varchar(3) primary key not null, decimals integer not null, name varchar(60) not null, iso varchar(3) not null)');
    DB::statement('CREATE TABLE code_list_orders (id integer primary key, amount decimal(19,6) not null, currency varchar(3) not null, quantity decimal(19,6) not null, unit varchar(3) not null)');

    foreach (['EUR' => 2, 'JPY' => 0, 'CHF' => 2, 'BHD' => 3, 'USD' => 2, 'GBP' => 2, 'KWD' => 3] as $code => $decimals) {
        DB::table('code_list_currencies')->insert(['code' => $code, 'decimals' => $decimals, 'name' => "Name {$code}", 'iso' => $code]);
    }

    $service = new CodeListService();
    $this->app->instance(ODataServiceRegistryInterface::class, new class ($service) implements ODataServiceRegistryInterface {
        public function __construct(private readonly ODataServiceInterface $service) {}
        public function resolve(string $fullPath): ODataServiceInterface { return $this->service; }
        public function services(): array { return [$this->service]; }
    });
});

describe('code-list wiring in $metadata', function () {
    it('annotates the container with CurrencyCodes and UnitsOfMeasure', function () {
        $xml = $this->get('/odata/$metadata')->streamedContent();

        expect($xml)
            ->toContain('<Annotation Term="com.sap.vocabularies.CodeList.v1.CurrencyCodes"><Record Type="com.sap.vocabularies.CodeList.v1.CodeListSource"><PropertyValue Property="Url" String="../codelists@1.0.0/$metadata"/><PropertyValue Property="CollectionPath" String="Currencies"/></Record></Annotation>')
            ->toContain('Term="com.sap.vocabularies.CodeList.v1.UnitsOfMeasure"');
    });

    it('writes the Measures annotations as paths', function () {
        $xml = $this->get('/odata/$metadata')->streamedContent();

        expect($xml)
            ->toContain('<Annotation Term="Org.OData.Measures.V1.ISOCurrency" Path="currency"/>')
            ->toContain('<Annotation Term="Org.OData.Measures.V1.Unit" Path="unit"/>');
    });

    it('writes scale, text and standard code on the code-list key as paths', function () {
        $xml = $this->get('/odata/$metadata')->streamedContent();

        expect($xml)
            ->toContain('<Annotation Term="com.sap.vocabularies.Common.v1.UnitSpecificScale" Path="decimals"/>')
            ->toContain('<Annotation Term="com.sap.vocabularies.Common.v1.Text" Path="name"/>')
            ->toContain('<Annotation Term="com.sap.vocabularies.CodeList.v1.StandardCode" Path="iso"/>');
    });

    it('serves the same code-list wiring warm as cold', function () {
        [$cold] = (new CodeListService())->buildForCache();

        $dir       = sys_get_temp_dir() . '/code_list_cache_' . getmypid();
        $namespace = 'CodeListCacheTest\\Edm';
        (new LaravelUi5\OData\Service\Cache\EdmxWriter($cold, $dir . '/Edm', $namespace))->write();
        spl_autoload_register(function (string $class) use ($namespace, $dir) {
            $file = $dir . '/Edm/' . str_replace('\\', '/', substr($class, strlen($namespace) + 1)) . '.php';
            if (str_starts_with($class, $namespace . '\\') && file_exists($file)) {
                require_once $file;
            }
        });
        require_once $dir . '/Edm/Edmx.php';
        $edmxClass = $namespace . '\\Edmx';
        $warm      = new $edmxClass(); // @phpstan-ignore class.notFound (generated by EdmxWriter above)

        $serializer = new CsdlSerializer();

        try {
            expect($serializer->serialize($warm))
                ->toBe($serializer->serialize($cold))
                ->toContain('CodeList.v1.CurrencyCodes')
                ->toContain('Path="currency"');
        } finally {
            (new Illuminate\Filesystem\Filesystem())->deleteDirectory($dir);
        }
    });
});

describe('code-list delivery', function () {
    it('answers the request UI5 sends unpaged, with $select honoured', function () {
        // UI5 1.136: GET <set>?$select=<key>,<scale>,<text>,<standard> — no $top, no Prefer.
        $data = json_decode(
            $this->get('/odata/Currencies?$select=code,decimals,name,iso')->streamedContent(),
            true,
        );

        expect($data['value'])->toHaveCount(7)
            ->and($data)->not->toHaveKey('@odata.nextLink')
            ->and(array_keys($data['value'][0]))->toBe(['code', 'decimals', 'name', 'iso']);
    });

    it('pages only when the client asks for it', function () {
        $response = $this->get('/odata/Currencies?$select=code', ['Prefer' => 'odata.maxpagesize=3']);
        $data     = json_decode($response->streamedContent(), true);

        expect($data['value'])->toHaveCount(3)
            ->and($data)->toHaveKey('@odata.nextLink');
    });
});
