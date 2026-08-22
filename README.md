# laravelui5/odata

[![Packagist Version](https://img.shields.io/packagist/v/laravelui5/odata.svg)](https://packagist.org/packages/laravelui5/odata)
[![PHP Version](https://img.shields.io/packagist/dependency-v/laravelui5/odata/php.svg)](https://packagist.org/packages/laravelui5/odata)
[![License](https://img.shields.io/packagist/l/laravelui5/odata.svg)](./LICENSE)

**A read-only OData v4 engine for Laravel.** Point it at an Eloquent model and every client that
speaks OData — a UI5 table, Excel, Power BI, your own frontend — can filter, sort, page, project,
and traverse your data over plain HTTP. No controllers, no serializers, no query parameters you
have to invent and then document.

## The problem it solves

Every Laravel app that grows a frontend grows an API, and every one of them re-invents the same
five things: projection, filtering, sorting, paging, and a schema nobody can trust. Controllers,
resources, query scopes, an `?include=` convention, and a document that starts drifting in week
three. For every project. Every time.

OData already answers all five. It is an [OASIS Standard](https://www.oasis-open.org/committees/tc_home.php?wg_abbrev=odata),
published by ISO as ISO/IEC 20802, and spoken natively by SAP, Microsoft, Salesforce, Excel, and
every UI5 SmartControl. This package brings it to Laravel:

```
GET /odata/Products?$filter=price gt 10&$orderby=name&$top=20&$select=name,price
GET /odata/Products(42)?$expand=supplier
GET /odata/$metadata
```

Those URLs work the moment you declare a service. The last one returns a machine-readable schema,
generated from the same model that serves the data — so it cannot drift from the API it describes.

Not sure OData is the right call for your project?
[**Why OData?**](https://laravelui5.com/odata/getting-started/why-odata) makes the argument, and
names the cases where the answer is something else.

## What you get

- **Full query support** — `$filter`, `$select`, `$expand` (nested), `$orderby`, `$top`, `$skip`,
  `$count`, `$search`, `$compute`, and `$batch` with partial failure
- **A real schema** — `$metadata` as CSDL XML, service documents, functions, singletons, and
  [annotations](https://laravelui5.com/odata/annotations/overview) that carry meaning, not just types
- **Any backing store** — an Eloquent model, a SQL view, an external API, a directory of files;
  the key is the only hard requirement
- **Multiple services** in one application, each on its own route
- **Compiled schemas** — `php artisan odata:cache` pre-compiles the EDM to PHP classes, so no
  discovery happens at request time
- **Streamed responses** — large result sets never buffer in memory

Read-only by design: queries in, JSON out. Writes stay in your application, where validation and
business rules belong. Also out of scope: ETags, `$apply`, and OData actions.

## Requirements

PHP 8.4+ · Laravel 11, 12, or 13 · the `dom`, `json`, `libxml`, and `simplexml` extensions

## Install

```bash
composer require laravelui5/odata
php artisan vendor:publish --provider="LaravelUi5\OData\ODataServiceProvider"
```

The service provider registers itself through Laravel's auto-discovery. The published
`config/odata.php` controls the route prefix, middleware, streaming, page sizes, and the service
registry — see [Installation](https://laravelui5.com/odata/getting-started/installation) and the
[Configuration reference](https://laravelui5.com/odata/advanced/configuration).

## Quickstart

Declare a service:

```php
use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\Service\Contracts\EdmBuilderInterface;

class ProductService extends ODataService
{
    public function serviceUri(): string { return ''; }
    public function namespace(): string  { return 'App.Products'; }

    protected function configure(EdmBuilderInterface $builder): EdmBuilderInterface
    {
        $this->discoverModel(Product::class);

        return $builder->namespace($this->namespace());
    }
}
```

`discoverModel()` reads the table, maps columns to typed properties, detects the key, and turns
Eloquent relationships into navigation properties. Point `config/odata.php` at a registry that
returns the service, and `/odata/Products` is live.

The [**Quickstart**](https://laravelui5.com/odata/getting-started/quickstart) walks the whole path
in five minutes, from `make:model` to the first `$filter`.

## Talk to it from anything

The API is URLs, so the smallest possible client is `fetch`:

```js
const res = await fetch(
  "/odata/Products?$filter=active eq true&$orderby=price desc&$top=10",
  { headers: { Accept: "application/json" } },
);
const { value: products } = await res.json();
```

Ready-made clients exist where you would want them. UI5's `sap.ui.model.odata.v4.ODataModel` binds
tables and forms straight to an entity set — see the
[Smart Table](https://laravelui5.com/odata/recipes/sapui5-smart-table) and
[Value Help](https://laravelui5.com/odata/recipes/sapui5-valuehelp) recipes. Excel and Power BI
consume a service as an OData feed out of the box (*Get Data → From OData feed*). And npm carries
typed clients such as [`@odata/client`](https://www.npmjs.com/package/@odata/client) and
[`ra-data-odata-server`](https://www.npmjs.com/package/ra-data-odata-server) for react-admin.

## Documentation

Full documentation lives at **[laravelui5.com/odata](https://laravelui5.com/odata)**.

| Section | What's there |
|:---|:---|
| [Getting Started](https://laravelui5.com/odata/getting-started/why-odata) | why OData, the concepts, installation, quickstart |
| [Services](https://laravelui5.com/odata/services/defining-a-service) | defining a service, model discovery, manual schema, functions, multi-service |
| [Resolvers](https://laravelui5.com/odata/resolvers/eloquent-resolver) | Eloquent, raw SQL, custom entity sets, custom resolvers |
| [Query Options](https://laravelui5.com/odata/query-options/filter) | `$filter`, `$select`/`$expand`, `$orderby`, paging, `$search`, `$count`, `$compute` |
| [Metadata & Annotations](https://laravelui5.com/odata/metadata/metadata-document) | `$metadata`, service document, vocabulary terms |
| [Advanced](https://laravelui5.com/odata/advanced/architecture) | architecture, configuration, `$batch`, caching, error handling |
| [Recipes](https://laravelui5.com/odata/recipes/sapui5-smart-table) | UI5 Smart Table, value help, testing |
| [API Reference](https://laravelui5.com/api/odata/index.html) | generated class reference |

## Support

- **Bugs and feature requests** — [github.com/laravelui5/odata/issues](https://github.com/laravelui5/odata/issues)
- **Security** — please do not open a public issue; see [SECURITY.md](./SECURITY.md)
- **What shipped** — [CHANGELOG.md](./CHANGELOG.md) · **what's queued** — [ROADMAP.md](./ROADMAP.md)
- **The wider stack** — this engine is the MIT foundation of
  [LaravelUi5](https://laravelui5.com): Core adds UI5 artifact routing, the SDK adds the
  enterprise runtime

## Provenance

A clean-room rewrite of [flat3/lodata](https://github.com/flat3/lodata). Its protocol test suite
was the pivot: ~400 HTTP tests that define the OData wire contract this implementation has to
honor. No original implementation code was preserved; the refactored tests remain as the permanent
regression suite.

## License

MIT — see [LICENSE](./LICENSE).
