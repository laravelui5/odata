<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Annotation\Annotation;
use LaravelUi5\OData\Edm\Annotation\CollectionAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Edm\Container\EntityContainer;
use LaravelUi5\OData\Edm\Container\EntitySet;
use LaravelUi5\OData\Edm\Container\EnumMember;
use LaravelUi5\OData\Edm\Container\EnumType;
use LaravelUi5\OData\Edm\Container\FunctionImport;
use LaravelUi5\OData\Edm\Container\NavigationPropertyBinding;
use LaravelUi5\OData\Edm\Container\Singleton;
use LaravelUi5\OData\Edm\Contracts\EdmxInterface;
use LaravelUi5\OData\Edm\EdmFunction;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Edmx;
use LaravelUi5\OData\Edm\FunctionParameter;
use LaravelUi5\OData\Edm\IncludedSchema;
use LaravelUi5\OData\Edm\Property\NavigationProperty;
use LaravelUi5\OData\Edm\Property\Property;
use LaravelUi5\OData\Edm\Reference;
use LaravelUi5\OData\Edm\Schema;
use LaravelUi5\OData\Edm\Type\EntityType;
use LaravelUi5\OData\Edm\Type\PrimitiveType;
use LaravelUi5\OData\Fixtures\Models\AnnotatedAirport;
use LaravelUi5\OData\Service\Builder\EdmBuilder;
use LaravelUi5\OData\Service\Cache\EdmxWriter;
use LaravelUi5\OData\Service\Discovery\ModelDiscovery;
use LaravelUi5\OData\Service\Serialization\CsdlSerializer;
use LaravelUi5\OData\Tests\TestCase;

uses(TestCase::class);

/*
 * odata:cache must carry every annotation the serializer writes — on the
 * container, the schema, types, properties, navigation properties, sets,
 * singletons, function imports, functions, parameters, enum types and members —
 * and the edmx:References. Before 3.1.0 the warm path served none of them.
 */

function annotationsCacheText(string $term, string $value): Annotation
{
    return new Annotation($term, null, new ConstantAnnotationValue('String', $value));
}

/** An Edmx with an annotation on every element the serializer annotates. */
function annotationsCacheEdmx(): EdmxInterface
{
    $ns     = 'Test.Ns';
    $int32  = new PrimitiveType(EdmPrimitiveType::Int32);
    $string = new PrimitiveType(EdmPrimitiveType::String);

    $colour = new EnumType($ns, 'Colour', EdmPrimitiveType::Int32, false, [
        new EnumMember('Red', 1, [annotationsCacheText('Org.OData.Core.V1.Description', 'warm red')]),
        new EnumMember('Blue', 2),
    ], [annotationsCacheText('Org.OData.Core.V1.Description', 'The colours')]);

    $lineId = new Property('id', $int32);
    $line   = new EntityType(
        namespace: $ns,
        name: 'Line',
        key: [$lineId],
        declaredProperties: [$lineId],
    );

    $orderId = new Property('id', $int32);
    $order   = new EntityType(
        namespace: $ns,
        name: 'Order',
        key: [$orderId],
        declaredProperties: [
            $orderId,
            // Quotes and a backslash: the literal has to survive the round trip.
            new Property('note', $string, annotations: [
                annotationsCacheText('com.sap.vocabularies.Common.v1.Label', 'Say "hi" \\ bye'),
            ]),
            new Property('currency', $string),
            new Property('amount', new PrimitiveType(EdmPrimitiveType::Decimal), annotations: [
                new Annotation('Org.OData.Measures.V1.ISOCurrency', null, new ConstantAnnotationValue('Path', 'currency')),
            ]),
            new Property('colour', $colour),
        ],
        declaredNavigationProperties: [
            new NavigationProperty('lines', $line, isCollection: true, annotations: [
                annotationsCacheText('Org.OData.Core.V1.Description', 'the lines'),
            ]),
        ],
        annotations: [
            new Annotation('com.sap.vocabularies.UI.v1.LineItem', null, new CollectionAnnotationValue(
                new RecordAnnotationValue(
                    'com.sap.vocabularies.UI.v1.DataField',
                    new PropertyValue('Value', new ConstantAnnotationValue('Path', 'note')),
                    new PropertyValue('Label', new ConstantAnnotationValue('String', 'Note')),
                ),
                new RecordAnnotationValue(
                    'com.sap.vocabularies.UI.v1.DataField',
                    new PropertyValue('Value', new ConstantAnnotationValue('Path', 'amount')),
                ),
            )),
            new Annotation('com.sap.vocabularies.UI.v1.HeaderInfo', 'short', new RecordAnnotationValue(
                null,
                new PropertyValue('TypeName', new ConstantAnnotationValue('String', 'Order')),
            )),
        ],
    );

    $count = new EdmFunction(
        name: 'CountOrders',
        returnType: $int32,
        parameters: [new FunctionParameter('since', $string, annotations: [
            annotationsCacheText('Org.OData.Core.V1.Description', 'lower bound'),
        ])],
        annotations: [annotationsCacheText('Org.OData.Core.V1.Description', 'counts orders')],
    );

    $schema = new Schema(
        namespace: $ns,
        entityTypes: [$order, $line],
        enumTypes: [$colour],
        functions: [$count],
        annotations: [annotationsCacheText('Org.OData.Core.V1.Description', 'schema-level')],
    );

    $container = new EntityContainer(
        name: 'DefaultContainer',
        entitySets: [
            new EntitySet('Orders', $order, navigationPropertyBindings: [
                new NavigationPropertyBinding('lines', 'Lines'),
            ], annotations: [annotationsCacheText('Org.OData.Core.V1.Description', 'all orders')]),
            new EntitySet('Lines', $line),
        ],
        singletons: [
            new Singleton('LatestOrder', $order, annotations: [annotationsCacheText('Org.OData.Core.V1.Description', 'the latest')]),
        ],
        functionImports: [
            new FunctionImport('CountOrders', $count, annotations: [annotationsCacheText('Org.OData.Core.V1.Description', 'import')]),
        ],
        annotations: [
            new Annotation('com.sap.vocabularies.CodeList.v1.CurrencyCodes', null, new RecordAnnotationValue(
                null,
                new PropertyValue('Url', new ConstantAnnotationValue('String', '../codelists@1.0.0/$metadata')),
                new PropertyValue('CollectionPath', new ConstantAnnotationValue('String', 'Currencies')),
            )),
        ],
    );

    return new Edmx(
        '4.0',
        [new Reference(
            'https://sap.github.io/odata-vocabularies/vocabularies/Common.xml',
            [new IncludedSchema('com.sap.vocabularies.Common.v1', 'Common')],
        )],
        [$ns => $schema],
        $container,
    );
}

function writeAndLoadAnnotated(EdmxInterface $edmx, string $dir, string $namespace): EdmxInterface
{
    (new EdmxWriter($edmx, $dir . '/Edm', $namespace))->write();

    spl_autoload_register(function (string $class) use ($namespace, $dir) {
        if (!str_starts_with($class, $namespace . '\\')) {
            return;
        }
        $file = $dir . '/Edm/' . str_replace('\\', '/', substr($class, strlen($namespace) + 1)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });

    require_once $dir . '/Edm/Edmx.php';
    $edmxClass = $namespace . '\\Edmx';

    return new $edmxClass();
}

beforeEach(function () {
    $this->tmpDir = sys_get_temp_dir() . '/annotations_cache_test_' . getmypid();
});

afterEach(function () {
    if (is_dir($this->tmpDir)) {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tmpDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->tmpDir);
    }
});

describe('odata:cache → annotations survive the warm path', function () {
    it('serves the same $metadata warm and cold, every annotation and reference included', function () {
        $cold = annotationsCacheEdmx();
        $warm = writeAndLoadAnnotated($cold, $this->tmpDir . '/all', 'AnnotationsCacheAllTest\\Edm');

        $serializer = new CsdlSerializer();
        $xml        = $serializer->serialize($warm);

        expect($xml)->toBe($serializer->serialize($cold))
            ->toContain('com.sap.vocabularies.CodeList.v1.CurrencyCodes')
            ->toContain('Term="Org.OData.Measures.V1.ISOCurrency" Path="currency"')
            ->toContain('com.sap.vocabularies.UI.v1.LineItem')
            ->toContain('edmx:Reference');
    });

    it('keeps quotes and backslashes in annotation text', function () {
        $warm = writeAndLoadAnnotated(annotationsCacheEdmx(), $this->tmpDir . '/quotes', 'AnnotationsCacheQuotesTest\\Edm');

        $label = $warm->getSchema('Test.Ns')->getEntityType('Order')
            ->getProperty('note')->getAnnotations()[0]->getValue()->getValue();

        expect($label)->toBe('Say "hi" \\ bye');
    });

    it('answers getAnnotation() on a cached type', function () {
        $warm = writeAndLoadAnnotated(annotationsCacheEdmx(), $this->tmpDir . '/lookup', 'AnnotationsCacheLookupTest\\Edm');

        $order = $warm->getSchema('Test.Ns')->getEntityType('Order');

        expect($order->getAnnotation('com.sap.vocabularies.UI.v1.HeaderInfo', 'short'))->not->toBeNull()
            ->and($warm->getReference('https://sap.github.io/odata-vocabularies/vocabularies/Common.xml'))->not->toBeNull();
    });

    it('serves the vocabulary attributes of a discovered model warm as cold', function () {
        $discovery = new ModelDiscovery();
        $discovery->add(AnnotatedAirport::class);
        $builder = (new EdmBuilder())->namespace('Test.Ns');
        $discovery->apply($builder, 'Test.Ns');
        $cold = $builder->build();

        $warm = writeAndLoadAnnotated($cold, $this->tmpDir . '/airport', 'AnnotationsCacheAirportTest\\Edm');

        $serializer = new CsdlSerializer();

        expect($serializer->serialize($warm))
            ->toBe($serializer->serialize($cold))
            ->toContain('com.sap.vocabularies.Common.v1.Label');
    });
});
