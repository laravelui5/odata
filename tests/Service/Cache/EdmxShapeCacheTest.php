<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Container\EntityContainer;
use LaravelUi5\OData\Edm\Container\EntitySet;
use LaravelUi5\OData\Edm\Container\FunctionImport;
use LaravelUi5\OData\Edm\Container\NavigationPropertyBinding;
use LaravelUi5\OData\Edm\Container\Singleton;
use LaravelUi5\OData\Edm\Contracts\EdmxInterface;
use LaravelUi5\OData\Edm\EdmFunction;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Edmx;
use LaravelUi5\OData\Edm\EntitySetPath;
use LaravelUi5\OData\Edm\FunctionParameter;
use LaravelUi5\OData\Edm\Property\NavigationProperty;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Schema;
use LaravelUi5\OData\Edm\Type\ComplexType;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Edm\Type\TypeDefinition;
use LaravelUi5\OData\Edm\Type\TypeFacets;
use LaravelUi5\OData\Service\Cache\EdmxWriter;
use LaravelUi5\OData\Service\Serialization\CsdlSerializer;

/*
 * OP28: the warm path reproduces the cold Edmx beyond annotations — complex
 * types (which did not even load), type definitions, base types and abstract /
 * open flags, keys that are not the first column, the full function shape,
 * function-import entity sets, singleton bindings, containment and OnDelete.
 */

function edmxShapeCold(): EdmxInterface
{
    $ns     = 'Shape.Ns';
    $int32  = new PrimitiveType(EdmPrimitiveType::Int32);
    $string = new PrimitiveType(EdmPrimitiveType::String);

    $code = new TypeDefinition($ns, 'Code', EdmPrimitiveType::String, new TypeFacets(nullable: true, maxLength: 3));

    $customer = new EntityType(
        namespace: $ns,
        name: 'Customer',
        key: [$customerId = new Property('id', $int32)],
        declaredProperties: [$customerId, new Property('name', $string)],
    );

    // Complex type with a base type and a navigation property back to an entity.
    $place   = new ComplexType($ns, 'Place', isAbstract: true, declaredProperties: [new Property('city', $string)]);
    $address = new ComplexType($ns, 'Address', baseType: $place, isOpen: true, declaredProperties: [
        new Property('street', $string),
        new Property('country', $code),
    ], declaredNavigationProperties: [
        new NavigationProperty('resident', $customer, isCollection: false),
    ]);

    // The key is the *second* declared property.
    $orderId = new Property('id', $int32);
    $order   = new EntityType(
        namespace: $ns,
        name: 'Order',
        isAbstract: false,
        isOpen: true,
        key: [$orderId],
        declaredProperties: [
            new Property('label', $string),
            $orderId,
            new Property('shipTo', $address),
            new Property('currency', $code),
        ],
        declaredNavigationProperties: [
            new NavigationProperty('customer', $customer, isCollection: false, isNullable: false, onDeleteAction: 'Cascade'),
            new NavigationProperty('notes', $customer, isCollection: true, isContainmentTarget: true),
        ],
    );

    // A derived type: no key of its own, falls back to the base.
    $rushOrder = new EntityType(
        namespace: $ns,
        name: 'RushOrder',
        baseType: $order,
        declaredProperties: [new Property('deadline', new PrimitiveType(EdmPrimitiveType::DateTimeOffset))],
    );

    $topCustomers = new EdmFunction(
        name: 'TopCustomers',
        isBound: true,
        isComposable: true,
        returnType: $customer,
        returnsCollection: true,
        isReturnTypeNullable: false,
        parameters: [
            new FunctionParameter('bindingParameter', $order, isCollection: true),
            new FunctionParameter('limit', $int32, isNullable: false),
            new FunctionParameter('currency', $code, facets: new TypeFacets(nullable: true, maxLength: 3)),
            new FunctionParameter('near', $address),
        ],
        entitySetPath: new EntitySetPath('bindingParameter', 'customer'),
    );
    $count = new EdmFunction(name: 'CountOrders', returnType: $int32, parameters: [new FunctionParameter('label', $string)]);

    $schema = new Schema(
        namespace: $ns,
        entityTypes: [$customer, $order, $rushOrder],
        complexTypes: [$place, $address],
        typeDefinitions: [$code],
        functions: [$topCustomers, $count],
    );

    $container = new EntityContainer(
        name: 'DefaultContainer',
        entitySets: [
            new EntitySet('Customers', $customer),
            new EntitySet('Orders', $order, navigationPropertyBindings: [new NavigationPropertyBinding('customer', 'Customers')]),
        ],
        singletons: [
            new Singleton('Latest', $order, [new NavigationPropertyBinding('customer', 'Customers')]),
        ],
        functionImports: [
            new FunctionImport('CountOrders', $count, entitySet: 'Orders', includedInServiceDocument: true),
        ],
    );

    return new Edmx('4.0', [], [$ns => $schema], $container);
}

function edmxShapeWarm(EdmxInterface $cold, string $dir, string $namespace): EdmxInterface
{
    (new EdmxWriter($cold, $dir . '/Edm', $namespace))->write();

    spl_autoload_register(function (string $class) use ($namespace, $dir) {
        $file = $dir . '/Edm/' . str_replace('\\', '/', substr($class, strlen($namespace) + 1)) . '.php';
        if (str_starts_with($class, $namespace . '\\') && file_exists($file)) {
            require_once $file;
        }
    });

    require_once $dir . '/Edm/Edmx.php';
    $edmxClass = $namespace . '\\Edmx';

    return new $edmxClass();
}

beforeEach(function () {
    $this->tmpDir = sys_get_temp_dir() . '/edmx_shape_cache_' . getmypid();
});

afterEach(function () {
    (new Illuminate\Filesystem\Filesystem())->deleteDirectory($this->tmpDir);
});

describe('odata:cache → the warm Edmx keeps its shape', function () {
    it('serves the same $metadata warm and cold', function () {
        $cold = edmxShapeCold();
        $warm = edmxShapeWarm($cold, $this->tmpDir . '/all', 'EdmxShapeAllTest\\Edm');

        $serializer = new CsdlSerializer();

        expect($serializer->serialize($warm))->toBe($serializer->serialize($cold));
    });

    it('loads complex types and keeps their base type and navigation', function () {
        $warm    = edmxShapeWarm(edmxShapeCold(), $this->tmpDir . '/complex', 'EdmxShapeComplexTest\\Edm');
        $address = $warm->getSchema('Shape.Ns')->getComplexType('Address');

        expect($address->getBaseType()?->getName())->toBe('Place')
            ->and($address->getBaseType()->isAbstract())->toBeTrue()
            ->and($address->isOpen())->toBeTrue()
            ->and($address->getProperty('city'))->not->toBeNull()   // inherited
            ->and($address->getNavigationProperty('resident')?->getTargetType()->getName())->toBe('Customer');
    });

    it('keys by name, and a derived type falls back to its base key', function () {
        $schema = edmxShapeWarm(edmxShapeCold(), $this->tmpDir . '/keys', 'EdmxShapeKeysTest\\Edm')->getSchema('Shape.Ns');

        expect(array_map(fn ($p) => $p->getName(), $schema->getEntityType('Order')->getKey()))->toBe(['id'])
            ->and(array_map(fn ($p) => $p->getName(), $schema->getEntityType('RushOrder')->getKey()))->toBe(['id'])
            ->and($schema->getEntityType('RushOrder')->getProperty('label'))->not->toBeNull();
    });

    it('keeps the full shape of functions and function imports', function () {
        $warm = edmxShapeWarm(edmxShapeCold(), $this->tmpDir . '/functions', 'EdmxShapeFunctionsTest\\Edm');
        $top  = $warm->getSchema('Shape.Ns')->getFunctions()['TopCustomers'][0];

        expect($top->isBound())->toBeTrue()
            ->and($top->isComposable())->toBeTrue()
            ->and($top->returnsCollection())->toBeTrue()
            ->and($top->isReturnTypeNullable())->toBeFalse()
            ->and($top->getReturnType()->getQualifiedName())->toBe('Shape.Ns.Customer')
            ->and((string) $top->getEntitySetPath())->toBe('bindingParameter/customer')
            ->and($top->getParameter('bindingParameter')->isCollection())->toBeTrue()
            ->and($top->getParameter('currency')->getType()->getQualifiedName())->toBe('Shape.Ns.Code')
            ->and($warm->getEntityContainer()->getFunctionImport('CountOrders')->getEntitySet())->toBe('Orders');
    });

    it('writes Nullable once on a parameter with facets, and never on a TypeDefinition', function () {
        $xml = (new CsdlSerializer())->serialize(edmxShapeCold());

        expect($xml)
            ->toContain('<TypeDefinition Name="Code" UnderlyingType="Edm.String" MaxLength="3"/>')
            ->toContain('<Parameter Name="currency" Type="Shape.Ns.Code" Nullable="true" MaxLength="3"/>');
    });
});
