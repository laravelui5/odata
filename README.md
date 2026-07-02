# laravelui5/odata

A read-only OData v4 engine for Laravel 11+, built for OpenUI5 frontends.

This package is a clean-room rewrite of [flat3/lodata](https://github.com/flat3/lodata). Its protocol test suite served as the pivot: ~400 HTTP tests define the OData wire contract the new implementation must honor. No original implementation code was preserved; only relevant, refactored tests remain, forming the permanent regression suite.

> **Release acceptance.** Beyond the regression suite, releases are validated in production by [timesheet.biz](https://timesheet.biz), which runs this engine as its OData layer. There is no separate acceptance host — production use is the gate.

## What it does

- read-only [OData v4](https://docs.oasis-open.org/odata/odata/v4.01/odata-v4.01-part1-protocol.html) engine
- Supports multiple service endpoints
- Supports schema caching (no discovery at request time; `php artisan odata:cache` pre-compiles the EDM to PHP classes)
- Serves `$metadata`, service documents, entity collections, single entities, navigation, functions, singletons
- Full query support: `$filter`, `$select`, `$orderby`, `$expand` (nested), `$top`, `$skip`, `$count`, `$search`, `$compute`
- Supports `$batch` with partial failure
- Supports server-driven paging via `Prefer: odata.maxpagesize`
- Serves streamed responses (large result sets never buffer in memory)

## Requirements

- PHP 8.4+
- Laravel 11+

## Installation

```bash
composer require laravelui5/odata
```

The service provider registers automatically. Publish the config:

```bash
php artisan vendor:publish --tag=config --provider="LaravelUi5\OData\ODataServiceProvider"
```

## Quick start

### 1. Define a service

```php
namespace App\OData;

use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Driver\Sql\EloquentEntitySetResolver;
use LaravelUi5\OData\Edm\Container\EntitySet;
use LaravelUi5\OData\Edm\Contracts\Container\PrimitiveTypeEnum;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;
use LaravelUi5\OData\Service\Contracts\RuntimeSchemaBuilderInterface;

class PartnerService extends ODataService
{
    public function serviceUri(): string  { return 'partners'; }
    public function namespace(): string   { return 'io.pragmatiqu.partners'; }

    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        $this->discoverModel(Partner::class);
        
        return $builder->namespace($this->namespace());
    }
}
```

### 2. Register the service

Create a registry that returns your service:

```php
namespace App\OData;

use LaravelUi5\OData\Service\Contracts\ODataServiceInterface;
use LaravelUi5\OData\Service\Contracts\ODataServiceRegistryInterface;

class AppServiceRegistry implements ODataServiceRegistryInterface
{
    public function services(): array
    {
        return [new PartnerService()];
    }

    public function resolve(string $fullPath): ODataServiceInterface
    {
        return new PartnerService();
    }
}
```

Point the config at it in `config/odata.php`:

```php
'service_registry' => App\OData\AppServiceRegistry::class,
```

### 3. Use it

```
GET /odata/partners/Partners              → entity collection
GET /odata/partners/Partners(1)           → single entity
GET /odata/partners/Partners?$filter=name eq 'Acme'
GET /odata/partners/Partners?$select=id,name&$top=10&$orderby=name
GET /odata/partners/$metadata             → CSDL XML
```

## Configuration

Published to `config/odata.php`:

| Key                  | Default                       | Description                                       |
|:---------------------|:------------------------------|:--------------------------------------------------|
| `prefix`             | `odata`                       | URL route prefix                                  |
| `middleware`         | `[]`                          | Middleware for OData routes                       |
| `streaming`          | `true`                        | Stream JSON responses                             |
| `namespace`          | `com.example.odata`           | Default XML namespace                             |
| `version`            | `4.0`                         | OData protocol version                            |
| `service_registry`   | `ODataServiceRegistry::class` | Registry implementation                           |
| `pagination.max`     | `null`                        | Server max page size cap                          |
| `pagination.default` | `200`                         | Default page size when client sends no preference |

## Schema caching

For production, pre-compile the EDM object graph to PHP classes:

```bash
php artisan odata:cache    # generates Edm/ directory next to each service class
php artisan odata:clear    # removes generated Edm/ directories
```

Cached classes are plain `readonly` PHP implementing `Edm\Contracts\` interfaces.
On warm boot, `ODataService::schema()` loads them directly — skipping the builder entirely.

## Architecture

Three concentric layers. Each layer only depends on the layers inside it.

```
Http\         → routes requests to the engine
Protocol\     → parses OData URLs, plans queries, executes via handlers
Service\      → contracts, builders, caching, serialization
Edm\          → pure read-only metamodel (zero framework dependencies)
Driver\       → implements resolver contracts (Eloquent/SQL)
```

Services declare their schema in `configure()` (what the service looks like) and
bind resolvers in `bindResolvers()` (how to fetch the data). The engine never
touches the schema after planning — it works entirely from the resolved query plan.

## Roadmap

Post-GA improvements for the extensibility layer:

- **Serialize EDXML** on `odata:cache` and serve XML directly
- **In-memory filter/sort/paginate helpers for custom resolvers.** Tier 3 (fully custom) resolvers must interpret the filter AST, ordering, and pagination themselves. A small utility (e.g. `InMemoryFilter::apply($rows, $plan)`) would reduce boilerplate for resolvers backed by REST APIs, PHP computation, or other non-SQL sources.
- **Nullable column declaration in AbstractEntitySet.** `columns()` maps names to `PrimitiveTypeEnum` but cannot express nullability. Marking a column nullable currently requires overriding `entityType()`. A declarative option (e.g. nullable enum wrapper or separate `nullable()` method) would close this gap.
- **Composite key order validation.** `entityType()` resolves key properties in column-declaration order, not in the order returned by `key()`. A validation warning during schema build would catch accidental reordering.
- **Discovery logging for skipped relations.** Polymorphic and through-relations on Eloquent models are silently ignored during discovery. A `logger->debug()` message would help developers understand why a navigation property is absent from `$metadata`.

## Not in scope (by design)

- Write operations (POST/PUT/PATCH/DELETE)
- ETags / conditional requests
- `$apply` (aggregation)
- Actions

## License

MIT
