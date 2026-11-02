<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Container\EntitySet;
use LaravelUi5\OData\Edm\Container\FunctionImport;
use LaravelUi5\OData\Edm\Container\Singleton;
use LaravelUi5\OData\Edm\EdmFunction;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Protocol\Execution\ServiceDocumentHandler;
use LaravelUi5\OData\Protocol\Planning\ServiceDocumentQueryPlan;
use LaravelUi5\OData\Service\Builder\EdmBuilder;
use LaravelUi5\OData\Service\Serialization\CsdlSerializer;
use LaravelUi5\OData\Tests\TestCase;

/*
 * OP11 (decided 2026-09-21, built 2026-11-02): the service document honours IncludeInServiceDocument on entity sets and
 * function imports, and lists function imports at all. A function import is listed by default, like an entity set.
 */

uses(TestCase::class);

function serviceDocumentEdmx(): \LaravelUi5\OData\Edm\Contracts\EdmxInterface
{
    $int32 = new PrimitiveType(EdmPrimitiveType::Int32);
    $key   = new Property('id', $int32);
    $type  = new EntityType(namespace: 'Test.Ns', name: 'Thing', key: [$key], declaredProperties: [$key]);
    $count = new EdmFunction(name: 'CountThings', returnType: $int32);
    $audit = new EdmFunction(name: 'AuditThings', returnType: $int32);

    return (new EdmBuilder())
        ->namespace('Test.Ns')
        ->addEntityType($type)
        ->addEntitySet(new EntitySet('Things', $type))
        ->addEntitySet(new EntitySet('InternalThings', $type, includedInServiceDocument: false))
        ->addSingleton(new Singleton('FirstThing', $type))
        ->addFunction($count)
        ->addFunction($audit)
        ->addFunctionImport(new FunctionImport('CountThings', $count))
        ->addFunctionImport(new FunctionImport('AuditThings', $audit, includedInServiceDocument: false))
        ->build();
}

/** @return list<array{name: string, kind: string, url: string}> */
function serviceDocumentEntries(): array
{
    ob_start();
    (new ServiceDocumentHandler('http://localhost/odata/'))
        ->handle(new ServiceDocumentQueryPlan(serviceDocumentEdmx()))
        ->sendContent();

    return json_decode((string) ob_get_clean(), true)['value'];
}

it('lists what is included, singletons always, function imports too', function () {
    expect(serviceDocumentEntries())->toBe([
        ['name' => 'Things', 'kind' => 'EntitySet', 'url' => 'Things'],
        ['name' => 'FirstThing', 'kind' => 'Singleton', 'url' => 'FirstThing'],
        ['name' => 'CountThings', 'kind' => 'FunctionImport', 'url' => 'CountThings'],
    ]);
});

it('leaves out an entity set and a function import taken out of the service document', function () {
    expect(array_column(serviceDocumentEntries(), 'name'))
        ->not->toContain('InternalThings')
        ->not->toContain('AuditThings');
});

it('lists a function import by default, and says so in $metadata', function () {
    expect((new FunctionImport('X', new EdmFunction(name: 'X', returnType: new PrimitiveType(EdmPrimitiveType::Int32))))->isIncludedInServiceDocument())
        ->toBeTrue()
        ->and((new CsdlSerializer())->serialize(serviceDocumentEdmx()))
        ->toContain('<FunctionImport Name="CountThings" Function="Test.Ns.CountThings" IncludeInServiceDocument="true"/>')
        ->toContain('<FunctionImport Name="AuditThings" Function="Test.Ns.AuditThings"/>');
});
