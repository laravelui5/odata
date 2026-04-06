<?php

declare(strict_types=1);

use LaravelUi5\OData\Edm\Annotation\Annotation;
use LaravelUi5\OData\Edm\Annotation\CollectionAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\ConstantAnnotationValue;
use LaravelUi5\OData\Edm\Annotation\PropertyValue;
use LaravelUi5\OData\Edm\Annotation\RecordAnnotationValue;
use LaravelUi5\OData\Service\Serialization\CsdlSerializer;

// ── Minimal fixture helpers ────────────────────────────────────────────────────
// Each helper returns an anonymous class implementing only the interface methods
// needed by CsdlSerializer. Unneeded methods return safe empty defaults.

/**
 * Builds a minimal EdmxInterface fixture.
 *
 * @param  string         $version
 * @param  array          $references  list<ReferenceInterface>
 * @param  array          $schemas     array<string, SchemaInterface>
 * @param  object         $container   EntityContainerInterface
 */
function makeEdmx(
    string $version,
    array $references,
    array $schemas,
    object $container,
): object {
    return new class ($version, $references, $schemas, $container)
        implements \LaravelUi5\OData\Edm\Contracts\EdmxInterface {
        public function __construct(
            private string $version,
            private array $references,
            private array $schemas,
            private object $container,
        ) {}
        public function getVersion(): string { return $this->version; }
        public function getReferences(): array { return $this->references; }
        public function getReference(string $uri): ?\LaravelUi5\OData\Edm\Contracts\ReferenceInterface { return null; }
        public function getSchemas(): array { return $this->schemas; }
        public function getSchema(string $namespace): ?\LaravelUi5\OData\Edm\Contracts\SchemaInterface { return null; }
        public function getEntityContainer(): \LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface
        {
            return $this->container;
        }
    };
}

/**
 * Builds a minimal EntityContainerInterface fixture.
 */
function makeContainer(string $name, array $sets = [], array $singletons = [], array $imports = [], array $annotations = []): object
{
    return new class ($name, $sets, $singletons, $imports, $annotations)
        implements \LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface {
        public function __construct(
            private string $name,
            private array $sets,
            private array $singletons,
            private array $imports,
            private array $annotations,
        ) {}
        public function getName(): string { return $this->name; }
        public function getEntitySets(): array { return $this->sets; }
        public function getEntitySet(string $name): ?\LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface { return null; }
        public function getSingletons(): array { return $this->singletons; }
        public function getSingleton(string $name): ?\LaravelUi5\OData\Edm\Contracts\Container\SingletonInterface { return null; }
        public function getFunctionImports(): array { return $this->imports; }
        public function getFunctionImport(string $name): ?\LaravelUi5\OData\Edm\Contracts\Container\FunctionImportInterface { return null; }
        public function getExtendsName(): ?string { return null; }
        public function getAnnotations(): array { return $this->annotations; }
        public function getAnnotation(string $term, ?string $qualifier = null): ?\LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface { return null; }
    };
}

/**
 * Builds a minimal SchemaInterface fixture.
 */
function makeSchema(
    string $namespace,
    object $container,
    array $entityTypes = [],
    array $complexTypes = [],
    array $enumTypes = [],
    array $typeDefs = [],
    array $functions = [],
    array $annotations = [],
    ?string $alias = null,
): object {
    return new class ($namespace, $alias, $entityTypes, $complexTypes, $enumTypes, $typeDefs, $functions, $annotations)
        implements \LaravelUi5\OData\Edm\Contracts\SchemaInterface {
        public function __construct(
            private string $namespace,
            private ?string $alias,
            private array $entityTypes,
            private array $complexTypes,
            private array $enumTypes,
            private array $typeDefs,
            private array $functions,
            private array $annotations,
        ) {}
        public function getName(): string { return $this->namespace; }
        public function getNamespace(): string { return $this->namespace; }
        public function getAlias(): ?string { return $this->alias; }
        public function getEntityTypes(): array { return $this->entityTypes; }
        public function getEntityType(string $name): ?\LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface { return null; }
        public function getComplexTypes(): array { return $this->complexTypes; }
        public function getComplexType(string $name): ?\LaravelUi5\OData\Edm\Contracts\Type\ComplexTypeInterface { return null; }
        public function getEnumTypes(): array { return $this->enumTypes; }
        public function getEnumType(string $name): ?\LaravelUi5\OData\Edm\Contracts\Container\EnumTypeInterface { return null; }
        public function getTypeDefinitions(): array { return $this->typeDefs; }
        public function getTypeDefinition(string $name): ?\LaravelUi5\OData\Edm\Contracts\Type\TypeDefinitionInterface { return null; }
        public function getFunctions(): array { return $this->functions; }
        public function getFunction(string $name): array { return []; }
        public function getAnnotations(): array { return $this->annotations; }
        public function getAnnotation(string $term, ?string $qualifier = null): ?\LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface { return null; }
    };
}

// ── Tests ──────────────────────────────────────────────────────────────────────

describe('CsdlSerializer::serialize', function () {

    describe('document root', function () {
        it('emits the edmx root element with the correct version', function () {
            $container = makeContainer('DefaultContainer');
            $schema    = makeSchema('Test.Service', $container);
            $edmx      = makeEdmx('4.01', [], [$schema->getNamespace() => $schema], $container);

            $xml = (new CsdlSerializer)->serialize($edmx);

            expect($xml)
                ->toContain('xmlns:edmx="http://docs.oasis-open.org/odata/ns/edmx"')
                ->toContain('Version="4.01"');
        });

        it('emits a Reference element with Uri and Include', function () {
            $include = new class implements \LaravelUi5\OData\Edm\Contracts\IncludedSchemaInterface {
                public function getNamespace(): string { return 'Org.OData.Core.V1'; }
                public function getAlias(): ?string { return 'Core'; }
            };
            $reference = new class ($include) implements \LaravelUi5\OData\Edm\Contracts\ReferenceInterface {
                public function __construct(private object $include) {}
                public function getUri(): string { return 'https://example.org/Core.xml'; }
                public function getIncludes(): array { return [$this->include]; }
                public function getInclude(string $namespace): ?\LaravelUi5\OData\Edm\Contracts\IncludedSchemaInterface { return null; }
                public function getAnnotations(): array { return []; }
                public function getAnnotation(string $term, ?string $qualifier = null): ?\LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface { return null; }
            };
            $container = makeContainer('DefaultContainer');
            $schema    = makeSchema('Test.Service', $container);
            $edmx      = makeEdmx('4.01', [$reference], [$schema->getNamespace() => $schema], $container);

            $xml = (new CsdlSerializer)->serialize($edmx);

            expect($xml)
                ->toContain('Uri="https://example.org/Core.xml"')
                ->toContain('Namespace="Org.OData.Core.V1"')
                ->toContain('Alias="Core"');
        });
    });

    describe('annotations — constant values', function () {
        it('serializes a String annotation as an inline attribute', function () {
            $annotation = new Annotation('Org.OData.Core.V1.Description', null,
                new ConstantAnnotationValue('String', 'hello world'));

            $container = makeContainer('DefaultContainer', [], [], [], [$annotation]);
            $schema    = makeSchema('Test.Service', $container, annotations: [$annotation]);
            $edmx      = makeEdmx('4.01', [], [$schema->getNamespace() => $schema], $container);

            $xml = (new CsdlSerializer)->serialize($edmx);

            expect($xml)->toContain('<Annotation Term="Org.OData.Core.V1.Description" String="hello world"/>');
        });

        it('serializes a Bool annotation as an inline attribute', function () {
            $annotation = new Annotation('Org.OData.Core.V1.DefaultNamespace', null,
                new ConstantAnnotationValue('Bool', 'true'));

            $container = makeContainer('DefaultContainer', [], [], [], [$annotation]);
            $schema    = makeSchema('Test.Service', $container, annotations: [$annotation]);
            $edmx      = makeEdmx('4.01', [], [$schema->getNamespace() => $schema], $container);

            $xml = (new CsdlSerializer)->serialize($edmx);

            expect($xml)->toContain('<Annotation Term="Org.OData.Core.V1.DefaultNamespace" Bool="true"/>');
        });

        it('serializes an Integer annotation as Int XML attribute', function () {
            $annotation = new Annotation('Some.Term', null,
                new ConstantAnnotationValue('Integer', '42'));

            $container = makeContainer('DefaultContainer', [], [], [], [$annotation]);
            $schema    = makeSchema('Test.Service', $container, annotations: [$annotation]);
            $edmx      = makeEdmx('4.01', [], [$schema->getNamespace() => $schema], $container);

            $xml = (new CsdlSerializer)->serialize($edmx);

            expect($xml)->toContain('<Annotation Term="Some.Term" Int="42"/>');
        });

        it('serializes an EnumMember annotation as an inline attribute', function () {
            $annotation = new Annotation('Org.OData.Capabilities.V1.ConformanceLevel', null,
                new ConstantAnnotationValue('EnumMember', 'Org.OData.Capabilities.V1.ConformanceLevelType/Advanced'));

            $container = makeContainer('DefaultContainer', [], [], [], [$annotation]);
            $schema    = makeSchema('Test.Service', $container, annotations: [$annotation]);
            $edmx      = makeEdmx('4.01', [], [$schema->getNamespace() => $schema], $container);

            $xml = (new CsdlSerializer)->serialize($edmx);

            expect($xml)->toContain('EnumMember="Org.OData.Capabilities.V1.ConformanceLevelType/Advanced"');
        });

        it('writes a qualifier attribute when one is present', function () {
            $annotation = new Annotation('Org.OData.Core.V1.Description', 'Mobile',
                new ConstantAnnotationValue('String', 'short label'));

            $container = makeContainer('DefaultContainer', [], [], [], [$annotation]);
            $schema    = makeSchema('Test.Service', $container, annotations: [$annotation]);
            $edmx      = makeEdmx('4.01', [], [$schema->getNamespace() => $schema], $container);

            $xml = (new CsdlSerializer)->serialize($edmx);

            expect($xml)->toContain('Qualifier="Mobile"');
        });
    });

    describe('annotations — record values', function () {
        it('serializes a Record annotation with PropertyValue children', function () {
            $record = new RecordAnnotationValue(
                null,
                new PropertyValue('Countable', new ConstantAnnotationValue('Bool', 'true')),
            );
            $annotation = new Annotation('Org.OData.Capabilities.V1.CountRestrictions', null, $record);

            $container = makeContainer('DefaultContainer', [], [], [], [$annotation]);
            $schema    = makeSchema('Test.Service', $container, annotations: [$annotation]);
            $edmx      = makeEdmx('4.01', [], [$schema->getNamespace() => $schema], $container);

            $xml = (new CsdlSerializer)->serialize($edmx);

            expect($xml)
                ->toContain('<Annotation Term="Org.OData.Capabilities.V1.CountRestrictions">')
                ->toContain('<Record>')
                ->toContain('<PropertyValue Property="Countable" Bool="true"/>');
        });

        it('serializes a typed Record with Type attribute', function () {
            $record = new RecordAnnotationValue(
                'UI.DataField',
                new PropertyValue('Label', new ConstantAnnotationValue('String', 'Name')),
            );
            $annotation = new Annotation('UI.LineItem', null, $record);

            $container = makeContainer('DefaultContainer', [], [], [], [$annotation]);
            $schema    = makeSchema('Test.Service', $container, annotations: [$annotation]);
            $edmx      = makeEdmx('4.01', [], [$schema->getNamespace() => $schema], $container);

            $xml = (new CsdlSerializer)->serialize($edmx);

            expect($xml)->toContain('<Record Type="UI.DataField">');
        });
    });

    describe('annotations — collection values', function () {
        it('serializes a Collection of String constants as child elements', function () {
            $collection = new CollectionAnnotationValue(
                new ConstantAnnotationValue('String', 'application/json'),
                new ConstantAnnotationValue('String', 'application/xml'),
            );
            $annotation = new Annotation('Org.OData.Capabilities.V1.SupportedFormats', null, $collection);

            $container = makeContainer('DefaultContainer', [], [], [], [$annotation]);
            $schema    = makeSchema('Test.Service', $container, annotations: [$annotation]);
            $edmx      = makeEdmx('4.01', [], [$schema->getNamespace() => $schema], $container);

            $xml = (new CsdlSerializer)->serialize($edmx);

            expect($xml)
                ->toContain('<Collection>')
                ->toContain('<String>application/json</String>')
                ->toContain('<String>application/xml</String>');
        });

        it('serializes a Collection of Records', function () {
            $record = new RecordAnnotationValue(
                null,
                new PropertyValue('Id', new ConstantAnnotationValue('String', 'http')),
            );
            $collection = new CollectionAnnotationValue($record);
            $annotation = new Annotation('Org.OData.Capabilities.V1.CallbackSupported', null,
                new RecordAnnotationValue(null,
                    new PropertyValue('CallbackProtocols', $collection),
                )
            );

            $container = makeContainer('DefaultContainer', [], [], [], [$annotation]);
            $schema    = makeSchema('Test.Service', $container, annotations: [$annotation]);
            $edmx      = makeEdmx('4.01', [], [$schema->getNamespace() => $schema], $container);

            $xml = (new CsdlSerializer)->serialize($edmx);

            expect($xml)
                ->toContain('<Collection>')
                ->toContain('<Record>')
                ->toContain('<PropertyValue Property="Id" String="http"/>');
        });
    });

    describe('schema structure', function () {
        it('emits the EntityContainer element with the correct name', function () {
            $container = makeContainer('PartnerContainer');
            $schema    = makeSchema('Partner.Service', $container);
            $edmx      = makeEdmx('4.01', [], [$schema->getNamespace() => $schema], $container);

            $xml = (new CsdlSerializer)->serialize($edmx);

            expect($xml)->toContain('<EntityContainer Name="PartnerContainer"');
        });

        it('emits a schema-level Annotations block targeting the container', function () {
            $annotation = new Annotation('Org.OData.Core.V1.ConventionalIDs', null,
                new ConstantAnnotationValue('Bool', 'true'));

            $container = makeContainer('DefaultContainer');
            $schema    = makeSchema('Test.Service', $container, annotations: [$annotation]);
            $edmx      = makeEdmx('4.01', [], [$schema->getNamespace() => $schema], $container);

            $xml = (new CsdlSerializer)->serialize($edmx);

            expect($xml)
                ->toContain('<Annotations Target="Test.Service.DefaultContainer">')
                ->toContain('<Annotation Term="Org.OData.Core.V1.ConventionalIDs" Bool="true"/>');
        });

        it('does not emit an Annotations block when there are no schema annotations', function () {
            $container = makeContainer('DefaultContainer');
            $schema    = makeSchema('Test.Service', $container);
            $edmx      = makeEdmx('4.01', [], [$schema->getNamespace() => $schema], $container);

            $xml = (new CsdlSerializer)->serialize($edmx);

            expect($xml)->not->toContain('<Annotations');
        });
    });
});
