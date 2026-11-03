<?php

declare(strict_types=1);

use LaravelUi5\OData\Console\VocabularyGenerator;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\EdmPrimitiveType;

/*
 * The generator end to end, offline: the catalog's URIs answered by fixture vocabularies.
 */

const GENERATOR_CORE_XML = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<edmx:Edmx xmlns:edmx="http://docs.oasis-open.org/odata/ns/edmx" Version="4.0">
  <edmx:DataServices>
    <Schema xmlns="http://docs.oasis-open.org/odata/ns/edm" Namespace="Org.OData.Core.V1" Alias="Core">
      <TypeDefinition Name="FilterExpressionType" UnderlyingType="Edm.String" />
      <Term Name="Description" Type="Edm.String" />
      <Term Name="Computed" Type="Core.Tag" AppliesTo="Property" />
      <Term Name="ProbeMinItems" Type="Edm.Int64" AppliesTo="Collection" />
      <Term Name="ProbeMemberTerm" Type="Edm.String" AppliesTo="Member" />
      <Term Name="ProbeImportTerm" Type="Edm.String" AppliesTo="FunctionImport Record Bogus" />
      <TypeDefinition Name="Tag" UnderlyingType="Edm.Boolean" />
    </Schema>
  </edmx:DataServices>
</edmx:Edmx>
XML;

const GENERATOR_COMMON_XML = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<edmx:Edmx xmlns:edmx="http://docs.oasis-open.org/odata/ns/edmx" Version="4.0">
  <edmx:Reference Uri="https://oasis-tcs.github.io/odata-vocabularies/vocabularies/Org.OData.Core.V1.xml">
    <edmx:Include Alias="Core" Namespace="Org.OData.Core.V1" />
  </edmx:Reference>
  <edmx:DataServices>
    <Schema xmlns="http://docs.oasis-open.org/odata/ns/edm" Namespace="com.sap.vocabularies.Common.v1" Alias="Common">
      <EnumType Name="FilterExpressionType"><Member Name="SingleValue" Value="0" /></EnumType>
      <ComplexType Name="RestrictionType">
        <Property Name="FromCore" Type="Core.FilterExpressionType" />
        <Property Name="FromCommon" Type="Common.FilterExpressionType" />
      </ComplexType>
      <Term Name="Label" Type="Edm.String" Nullable="false" />
      <ComplexType Name="ProbeRoot">
        <Property Name="Label" Type="Edm.String" />
      </ComplexType>
      <ComplexType Name="ProbeShared">
        <Property Name="A" Type="Edm.String" />
        <Property Name="ID" Type="Edm.String" />
      </ComplexType>
    </Schema>
  </edmx:DataServices>
</edmx:Edmx>
XML;

const GENERATOR_UI_XML = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<edmx:Edmx xmlns:edmx="http://docs.oasis-open.org/odata/ns/edmx" Version="4.0">
  <edmx:Reference Uri="https://sap.github.io/odata-vocabularies/vocabularies/Common.xml">
    <edmx:Include Namespace="com.sap.vocabularies.Common.v1" Alias="Common" />
  </edmx:Reference>
  <edmx:DataServices>
    <Schema xmlns="http://docs.oasis-open.org/odata/ns/edm" Namespace="com.sap.vocabularies.UI.v1" Alias="UI">
      <EnumType Name="ProbeCriticality"><Member Name="Neutral" Value="0" /><Member Name="Positive" Value="3" /></EnumType>
      <ComplexType Name="ProbeAbstract" Abstract="true" BaseType="Common.ProbeRoot">
        <Property Name="Criticality" Type="UI.ProbeCriticality" />
      </ComplexType>
      <ComplexType Name="ProbeField" BaseType="UI.ProbeAbstract">
        <Property Name="Value" Type="Edm.Untyped" Nullable="false" />
      </ComplexType>
      <ComplexType Name="ProbeFieldWithUrl" BaseType="UI.ProbeField">
        <Property Name="Value" Type="Edm.PrimitiveType" Nullable="false" />
        <Property Name="Url" Type="Edm.String" Nullable="false" />
        <Property Name="Items" Type="Collection(UI.ProbeAbstract)" />
      </ComplexType>
      <EnumType Name="ProbeFlags" IsFlags="true"><Member Name="Read" Value="1" /><Member Name="Write" Value="2" /></EnumType>
      <Term Name="ProbeLineItem" Type="Collection(UI.ProbeAbstract)" Nullable="false" AppliesTo="EntityType" />
      <Term Name="ProbeCriticalityTerm" Type="UI.ProbeCriticality" AppliesTo="Property" />
      <Term Name="ProbeFlagsTerm" Type="UI.ProbeFlags" Nullable="false" AppliesTo="Property" />
      <Term Name="ProbeMinimum" Type="Edm.PrimitiveType" Nullable="false" AppliesTo="Property" />
      <Term Name="ProbeDefaulted" Type="Edm.Int32" Nullable="false" DefaultValue="3" AppliesTo="Property" />
      <Term Name="ProbePath" Type="Edm.PropertyPath" AppliesTo="Property" />
      <Term Name="ProbeVariant" Type="UI.ProbeFieldWithUrl" AppliesTo="EntityType" />
      <Term Name="ProbeDefault" Type="UI.ProbeAbstract" Nullable="false" AppliesTo="Property" />
      <ComplexType Name="ProbeShared" BaseType="Common.ProbeShared">
        <Property Name="B" Type="Edm.Boolean" />
      </ComplexType>
    </Schema>
  </edmx:DataServices>
</edmx:Edmx>
XML;

beforeEach(function () {
    $this->outputRoot = sys_get_temp_dir() . '/odata-vocabulary-generator-' . bin2hex(random_bytes(4));
    $fetcher = static fn(string $uri): string => match (basename($uri)) {
        'Org.OData.Core.V1.xml' => GENERATOR_CORE_XML,
        'Common.xml'            => GENERATOR_COMMON_XML,
        'UI.xml'                => GENERATOR_UI_XML,
        default                 => '<?xml version="1.0"?><edmx:Edmx xmlns:edmx="http://docs.oasis-open.org/odata/ns/edmx">'
            . '<edmx:DataServices><Schema xmlns="http://docs.oasis-open.org/odata/ns/edm" Namespace="empty.'
            . basename($uri, '.xml') . '"/></edmx:DataServices></edmx:Edmx>',
    };
    $this->lines     = [];
    $this->generator = new VocabularyGenerator($this->outputRoot, fn(string $l) => $this->lines[] = $l, $fetcher);
    $this->read      = fn(string $path): string => (string) file_get_contents("{$this->outputRoot}/Vocabularies/{$path}");
});

afterEach(function () {
    if (is_dir($this->outputRoot)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->outputRoot, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->outputRoot);
    }
});

it('makes a term without AppliesTo an attribute on every target', function () {
    $this->generator->run();

    foreach (['Core/V1/Description.php', 'Common/V1/Label.php'] as $path) {
        $code = ($this->read)($path);
        expect($code)->toContain("#[Attribute(Attribute::TARGET_ALL)]\nfinal readonly class")
            ->and(substr_count($code, '::class,'))->toBe(14)
            ->and($code)->toContain('SchemaInterface::class,')
            ->and($code)->toContain('EnumMemberInterface::class,');
    }
});

it('keeps the targets of a term that names them', function () {
    $this->generator->run();

    $code = ($this->read)('Core/V1/Computed.php');
    expect($code)->toContain('#[Attribute(Attribute::TARGET_PROPERTY)]')
        ->and(substr_count($code, '::class,'))->toBe(1);
});

it('resolves a property type in the vocabulary its alias names, not by simple name', function () {
    $this->generator->run();

    $code = ($this->read)('Common/V1/RestrictionType.php');
    expect($code)->toContain('public string|Path|null $fromCore = null,')
        ->and($code)->toContain('public FilterExpressionType|Path|null $fromCommon = null,')
        ->and($code)->toContain("'FromCore' => ['fromCore', 'Edm.String', false],")
        ->and($code)->toContain("'FromCommon' => ['fromCommon', 'com.sap.vocabularies.Common.v1.FilterExpressionType', false],");
});

it('reads every vocabulary before generating a single one', function () {
    expect($this->generator->run('Common'))->toBeTrue();

    expect(($this->read)('Common/V1/RestrictionType.php'))->toContain('string|Path|null $fromCore')
        ->and(is_file("{$this->outputRoot}/Vocabularies/Core/V1/Description.php"))->toBeFalse();
});

it('refuses an unknown alias', function () {
    expect($this->generator->run('Nope'))->toBeFalse()
        ->and($this->lines)->toContain('ERROR: Unknown vocabulary alias: Nope');
});

it('writes complex types as a class hierarchy, across vocabularies', function () {
    $this->generator->run();

    expect(($this->read)('Ui/V1/ProbeAbstract.php'))
        ->toContain('use LaravelUi5\\OData\\Vocabularies\\Common\\V1\\ProbeRoot;')
        ->toContain('abstract readonly class ProbeAbstract extends ProbeRoot')
        ->toContain("public const string TYPE = 'com.sap.vocabularies.UI.v1.ProbeAbstract';")
        ->toContain('public ProbeCriticality|Path|null $criticality = null,')
        ->toContain("label: \$label,")
        ->and(($this->read)('Common/V1/ProbeRoot.php'))->toContain('readonly class ProbeRoot extends AbstractRecord')
        ->not->toContain('final readonly class ProbeRoot')
        ->and(($this->read)('Ui/V1/ProbeField.php'))->toContain("\nreadonly class ProbeField extends ProbeAbstract")
        ->and(($this->read)('Ui/V1/ProbeShared.php'))
        ->toContain('final readonly class ProbeShared extends \\LaravelUi5\\OData\\Vocabularies\\Common\\V1\\ProbeShared');

    // A redeclared field is handed to the parent that declared it; the narrowed type stands in FIELDS.
    expect(($this->read)('Ui/V1/ProbeFieldWithUrl.php'))
        ->toContain('final readonly class ProbeFieldWithUrl extends ProbeField')
        ->toContain("        mixed \$value = null,\n        public string|Path|null \$url = null,")
        ->toContain("            value: \$value,\n        );")
        ->toContain("'Value' => ['value', 'Edm.PrimitiveType', true],")
        ->toContain("@param list<ProbeAbstract>|Path|null \$items");
});

/** Loads the generated Probe* classes (they exist nowhere else) for the duration of $test. */
function withGeneratedProbes(string $outputRoot, Closure $test): void
{
    $root   = "{$outputRoot}/Vocabularies/";
    $loader = static function (string $class) use ($root): void {
        $prefix = 'LaravelUi5\\OData\\Vocabularies\\';
        $file   = $root . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (str_starts_with($class, $prefix) && str_contains($class, 'Probe') && is_file($file)) {
            require_once $file;
        }
    };
    spl_autoload_register($loader);
    try {
        $test();
    } finally {
        spl_autoload_unregister($loader);
    }
}

/** A generated class, built by reflection: it exists only once generated. */
function makeGenerated(string $class, array $args = []): object
{
    return (new ReflectionClass('LaravelUi5\\OData\\Vocabularies\\' . $class))->newInstanceArgs($args);
}

it('writes record classes that load and write their records', function () {
    $this->generator->run();

    withGeneratedProbes($this->outputRoot, function () {
        $class    = 'LaravelUi5\\OData\\Vocabularies\\Ui\\V1\\ProbeFieldWithUrl';
        $field    = 'LaravelUi5\\OData\\Vocabularies\\Ui\\V1\\ProbeField';
        $positive = constant('LaravelUi5\\OData\\Vocabularies\\Ui\\V1\\ProbeCriticality::Positive');
        $make     = static fn(string $c, array $args) => (new ReflectionClass($c))->newInstanceArgs($args);
        $record   = $make($class, [
            'label'       => 'Code',
            'criticality' => $positive,
            'value'       => 'code',
            'url'         => 'https://example.test',
            'items'       => [$make($field, ['value' => true])],
        ])->toRecord();

        expect($record->getType())->toBe('com.sap.vocabularies.UI.v1.ProbeFieldWithUrl')
            ->and(array_map(static fn($pv) => $pv->getProperty(), $record->getPropertyValues()))
            ->toBe(['Label', 'Criticality', 'Value', 'Url', 'Items'])
            ->and($record->getPropertyValue('Criticality')->getValue()->getValue())
            ->toBe('com.sap.vocabularies.UI.v1.ProbeCriticality/Positive')
            ->and($record->getPropertyValue('Items')->getValue()->getItems()[0]->getType())
            ->toBe('com.sap.vocabularies.UI.v1.ProbeField');

        expect(fn() => $make($class, ['value' => 'code'])->toRecord())
            ->toThrow(LogicException::class, 'requires the field Url');
    });
});

it('writes every term value through the value writer', function () {
    $this->generator->run();

    withGeneratedProbes($this->outputRoot, function () {
        $value = static fn(object $term) => $term->toAnnotation()->getValue();

        $lineItem = $value(makeGenerated('Ui\\V1\\ProbeLineItem', [[makeGenerated('Ui\\V1\\ProbeField', ['value' => new Path('code')])]]));
        expect($lineItem->getItems()[0]->getType())->toBe('com.sap.vocabularies.UI.v1.ProbeField')
            ->and($lineItem->getItems()[0]->getPropertyValue('Value')->getValue()->getKind())->toBe('Path');

        $positive = constant('LaravelUi5\\OData\\Vocabularies\\Ui\\V1\\ProbeCriticality::Positive');
        $read     = constant('LaravelUi5\\OData\\Vocabularies\\Ui\\V1\\ProbeFlags::Read');
        $write    = constant('LaravelUi5\\OData\\Vocabularies\\Ui\\V1\\ProbeFlags::Write');
        $constant = static fn(object $term) => $value($term)->getKind() . '=' . $value($term)->getValue();

        expect($constant(makeGenerated('Ui\\V1\\ProbeCriticalityTerm', [$positive])))
            ->toBe('EnumMember=com.sap.vocabularies.UI.v1.ProbeCriticality/Positive')
            ->and($constant(makeGenerated('Ui\\V1\\ProbeCriticalityTerm', [new Path('crit')])))->toBe('Path=crit')
            ->and($constant(makeGenerated('Ui\\V1\\ProbeFlagsTerm', [[$read, $write]])))
            ->toBe('EnumMember=com.sap.vocabularies.UI.v1.ProbeFlags/Read com.sap.vocabularies.UI.v1.ProbeFlags/Write')
            ->and($constant(makeGenerated('Ui\\V1\\ProbeMinimum', [0])))->toBe('Int=0')
            ->and($constant(makeGenerated('Ui\\V1\\ProbeMinimum', ['2026-01-01', EdmPrimitiveType::Date])))
            ->toBe('Date=2026-01-01')
            ->and($constant(makeGenerated('Ui\\V1\\ProbePath', ['code'])))->toBe('PropertyPath=code')
            // A value path where a SAP client reads one (CodeList.StandardCode through …/$Path).
            ->and($constant(makeGenerated('Ui\\V1\\ProbePath', [new Path('code')])))->toBe('Path=code')
            ->and($value(makeGenerated('Ui\\V1\\ProbeDefaulted')))->toBeNull()
            ->and($value(makeGenerated('Ui\\V1\\ProbeCriticalityTerm')))->toBeNull();

        expect(fn() => makeGenerated('Ui\\V1\\ProbeMinimum')->toAnnotation())
            ->toThrow(LogicException::class, 'The term com.sap.vocabularies.UI.v1.ProbeMinimum requires a value.')
            ->and(fn() => makeGenerated('Ui\\V1\\ProbeLineItem', [['code']])->toAnnotation())
            ->toThrow(LogicException::class, 'takes a record or an enumeration member, string given');
    });

    expect(($this->read)('Ui/V1/ProbeLineItem.php'))->toContain('/** @param list<ProbeAbstract> $value */')
        ->and(($this->read)('Ui/V1/ProbePath.php'))->toContain('public string|Path|null $value = null,')
        ->and(($this->read)('Ui/V1/ProbeFlagsTerm.php'))
        ->toContain('public ProbeFlags|array|Path|null $value = null,');
});

it('gives a term of one concrete record the record\'s fields and builds it through the record class', function () {
    $this->generator->run();

    expect(($this->read)('Ui/V1/ProbeVariant.php'))
        ->toContain("        public string|Path|null \$label = null,\n        public ProbeCriticality|Path|null \$criticality = null,\n        public mixed \$value = null,\n        public string|Path|null \$url = null,")
        ->toContain('@param list<ProbeAbstract>|Path|null $items')
        ->toContain("return (new ProbeFieldWithUrl(\n            label: \$this->label,")
        ->and(($this->read)('Common/V1/ProbeShared.php'))->toContain('public string|Path|null $id = null,')
        ->toContain("'ID' => ['id', 'Edm.String', false],");

    withGeneratedProbes($this->outputRoot, function () {
        $record = makeGenerated('Ui\\V1\\ProbeVariant', ['value' => 'code', 'url' => 'https://example.test', 'label' => 'Code'])
            ->toAnnotation()->getValue();
        expect($record->getType())->toBe('com.sap.vocabularies.UI.v1.ProbeFieldWithUrl')
            ->and(array_map(static fn($pv) => $pv->getProperty(), $record->getPropertyValues()))->toBe(['Label', 'Value', 'Url']);

        expect(fn() => makeGenerated('Ui\\V1\\ProbeVariant', ['value' => 'code'])->toAnnotation())
            ->toThrow(LogicException::class, 'requires the field Url');
    });
});

it('gives a term of an abstract record type a record of any type deriving from it', function () {
    $this->generator->run();

    expect(($this->read)('Ui/V1/ProbeDefault.php'))->toContain('public ProbeAbstract|Path|null $value = null,');

    withGeneratedProbes($this->outputRoot, function () {
        $record = makeGenerated('Ui\\V1\\ProbeDefault', [makeGenerated('Ui\\V1\\ProbeField', ['value' => 1])])
            ->toAnnotation()->getValue();
        expect($record->getType())->toBe('com.sap.vocabularies.UI.v1.ProbeField');

        expect(fn() => makeGenerated('Ui\\V1\\ProbeDefault')->toAnnotation())
            ->toThrow(LogicException::class, 'The term com.sap.vocabularies.UI.v1.ProbeDefault requires a value.');
    });
});

it('maps the AppliesTo values of the standard, Collection and Member included', function () {
    $this->generator->run();

    expect(($this->read)('Core/V1/ProbeMinItems.php'))
        ->toContain('#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]')
        ->toContain("        EntitySetInterface::class,\n        PropertyInterface::class,\n        NavigationPropertyInterface::class,\n")
        ->and(($this->read)('Core/V1/ProbeMemberTerm.php'))
        ->toContain('#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]')
        ->toContain('EnumMemberInterface::class,');

    // FunctionImport and Record are CSDL values without a PHP target: no attribute, no warning. Bogus is not CSDL.
    expect(($this->read)('Core/V1/ProbeImportTerm.php'))->not->toContain('#[Attribute')
        ->toContain('FunctionImportInterface::class,')
        ->and(array_values(array_filter($this->lines, static fn(string $l) => str_contains($l, 'WARN'))))
        ->toBe(["  WARN: Org.OData.Core.V1.ProbeImportTerm: AppliesTo value 'Bogus' is not defined by CSDL — skipped."]);
});
