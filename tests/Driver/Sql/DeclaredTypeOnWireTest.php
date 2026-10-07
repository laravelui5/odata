<?php

declare(strict_types=1);

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Http\CustomQueryOptions;
use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\AbstractEntitySet;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;
use LaravelUi5\OData\Tests\TestCase;

/*
 * OP02: a custom (SQL) entity set emits the type its columns() declare, not the
 * scalar the driver returns. The case that surfaced it: a computed
 * `case when … then 1 else 0 end` declared Edm.Boolean went out as `1`, and
 * UI5's V4 model refused it ("1" is of type number, expected boolean).
 */

uses(TestCase::class);

final readonly class DeclaredTypeFlags extends AbstractEntitySet
{
    public function entitySetName(): string
    {
        return 'Flags';
    }

    public function key(): array
    {
        return ['id'];
    }

    public function columns(): array
    {
        return [
            'id'       => EdmPrimitiveType::Int32,
            'writable' => EdmPrimitiveType::Boolean,
            'price'    => EdmPrimitiveType::Decimal,
            'count'    => EdmPrimitiveType::Int32,
        ];
    }

    public function query(CustomQueryOptions $options): Builder
    {
        return DB::query()->fromSub(
            "SELECT 1 AS id, CASE WHEN 1 = 1 THEN 1 ELSE 0 END AS writable, '12.50' AS price, '3' AS count
             UNION ALL
             SELECT 2, CASE WHEN 1 = 0 THEN 1 ELSE 0 END, '0.10', '0'",
            't',
        );
    }
}

final class DeclaredTypeService extends ODataService
{
    public function __construct()
    {
        parent::__construct(serviceUriValue: '', namespaceValue: 'Test.Ns');
    }

    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        $this->discoverCustomEntitySet(DeclaredTypeFlags::class);

        return $builder->namespace($this->namespace());
    }
}

beforeEach(function () {
    $this->withExceptionHandling();

    $service = new DeclaredTypeService();
    $this->app->instance(ODataServiceRegistryInterface::class, new class ($service) implements ODataServiceRegistryInterface {
        public function __construct(private readonly ODataServiceInterface $service) {}
        public function resolve(string $fullPath): ODataServiceInterface { return $this->service; }
        public function services(): array { return [$this->service]; }
    });
});

it('emits the declared Edm type, not the driver scalar', function () {
    $rows = json_decode($this->get('/odata/Flags?$orderby=id')->streamedContent(), true)['value'];

    expect($rows)->toBe([
        ['id' => 1, 'writable' => true,  'price' => 12.5, 'count' => 3],
        ['id' => 2, 'writable' => false, 'price' => 0.1,  'count' => 0],
    ]);
});

it('still writes decimals as strings when the client asks for IEEE754Compatible', function () {
    $rows = json_decode($this->get('/odata/Flags?$orderby=id', [
        'Accept' => 'application/json;odata.metadata=minimal;IEEE754Compatible=true',
    ])->streamedContent(), true)['value'];

    expect($rows[0])->toBe(['id' => 1, 'writable' => true, 'price' => '12.50', 'count' => 3]);
});
