<?php

declare(strict_types=1);

use LaravelUi5\OData\ODataService;
use LaravelUi5\OData\ODataServiceRegistry;
use LaravelUi5\OData\Tests\TestCase;

/*
 * OP25: one default for `odata.namespace`, read from config on every path. The
 * in-code fallback used to be `com.example.odata` while the published config said
 * `io.pragmatiqu`, so the namespace depended on whether the config was published.
 */

uses(TestCase::class);

it('falls back to the same default whether or not the config is published', function () {
    config(['odata' => array_diff_key(config('odata'), ['namespace' => true])]);   // no key at all
    $unpublished = (new ODataServiceRegistry())->resolve('')->namespace();

    config(['odata.namespace' => null]);                                            // ODATA_NAMESPACE=null
    $nulled = (new ODataServiceRegistry())->resolve('')->namespace();

    config(['odata.namespace' => (require dirname(__DIR__, 2) . '/config.php')['namespace']]);
    $published = (new ODataServiceRegistry())->resolve('')->namespace();

    expect($unpublished)->toBe(ODataService::DEFAULT_NAMESPACE)
        ->and($nulled)->toBe(ODataService::DEFAULT_NAMESPACE)
        ->and($published)->toBe(ODataService::DEFAULT_NAMESPACE);
});

it('reads a configured namespace on every path', function () {
    config(['odata.namespace' => 'com.acme.data']);

    expect((new ODataServiceRegistry())->resolve('')->namespace())->toBe('com.acme.data')
        ->and((new ODataService())->namespace())->toBe('com.acme.data');
});

it('keeps a namespace the service declares itself', function () {
    config(['odata.namespace' => 'com.acme.data']);

    expect((new ODataService(namespaceValue: 'Partners.Data'))->namespace())->toBe('Partners.Data');
});
