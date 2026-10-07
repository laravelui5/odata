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
 * OP22: discoverCustomEntitySet() builds the set through the container, so a
 * set with constructor dependencies works — the expectation Core sets with its
 * ExecutableInvoker, and what `resolvers/custom-entity-sets` promises.
 */

uses(TestCase::class);

final readonly class ContainerTenantLabel
{
    public function __construct(public string $value) {}
}

final readonly class ContainerBuiltSet extends AbstractEntitySet
{
    public function __construct(private ContainerTenantLabel $tenant)
    {
        parent::__construct();   // wires the set as its own query source
    }

    public function entitySetName(): string { return 'Labels'; }
    public function key(): array { return ['id']; }
    public function columns(): array { return ['id' => EdmPrimitiveType::Int32, 'tenant' => EdmPrimitiveType::String]; }

    public function query(CustomQueryOptions $options): Builder
    {
        return DB::query()->fromSub('SELECT 1 AS id, ' . DB::getPdo()->quote($this->tenant->value) . ' AS tenant', 't');
    }
}

final class ContainerBuiltService extends ODataService
{
    public function __construct()
    {
        parent::__construct(serviceUriValue: '', namespaceValue: 'Test.Ns');
    }

    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        $this->discoverCustomEntitySet(ContainerBuiltSet::class);

        return $builder->namespace($this->namespace());
    }
}

it('builds a custom entity set with constructor dependencies from the container', function () {
    $this->withExceptionHandling();
    $this->app->instance(ContainerTenantLabel::class, new ContainerTenantLabel('acme'));

    $service = new ContainerBuiltService();
    $this->app->instance(ODataServiceRegistryInterface::class, new class ($service) implements ODataServiceRegistryInterface {
        public function __construct(private readonly ODataServiceInterface $service) {}
        public function resolve(string $fullPath): ODataServiceInterface { return $this->service; }
        public function services(): array { return [$this->service]; }
    });

    $rows = json_decode($this->get('/odata/Labels')->streamedContent(), true)['value'];

    expect($rows)->toBe([['id' => 1, 'tenant' => 'acme']]);
});
