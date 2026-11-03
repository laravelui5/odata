<?php

declare(strict_types=1);

use LaravelUi5\OData\Console\VocabularyTypeModel;
use LaravelUi5\OData\Edm\Vocabularies\VocabularyEntry;

/*
 * The generator's type model: every reference resolved against the document it stands in, held under its qualified
 * name, with base types followed across vocabularies (OP07, step 1).
 */

function vocabularyXpath(string $schema, string $includes = ''): DOMXPath
{
    $dom = new DOMDocument();
    $dom->loadXML(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<edmx:Edmx xmlns:edmx="http://docs.oasis-open.org/odata/ns/edmx" Version="4.0">
  {$includes}
  <edmx:DataServices>
    {$schema}
  </edmx:DataServices>
</edmx:Edmx>
XML);

    return new DOMXPath($dom);
}

function vocabularyEntry(string $namespace, string $alias): VocabularyEntry
{
    return new VocabularyEntry($namespace, $alias, "https://example.test/{$alias}.xml", "Test\\{$alias}", []);
}

function loadedTypeModel(): VocabularyTypeModel
{
    $model = new VocabularyTypeModel();

    // "Base" is loaded second: a base type from a later vocabulary must still resolve.
    $model->load(vocabularyEntry('test.Ui.v1', 'Ui'), vocabularyXpath(<<<'XML'
<Schema xmlns="http://docs.oasis-open.org/odata/ns/edm" Namespace="test.Ui.v1" Alias="Ui">
  <TypeDefinition Name="Flag" UnderlyingType="B.Toggle" />
  <EnumType Name="Kind"><Member Name="Plain" Value="0" /><Member Name="Bold" Value="1" /></EnumType>
  <ComplexType Name="FieldAbstract" Abstract="true" BaseType="B.Labelled">
    <Property Name="Criticality" Type="Ui.Kind" />
  </ComplexType>
  <ComplexType Name="Field" BaseType="Ui.FieldAbstract">
    <Property Name="Value" Type="Edm.Untyped" Nullable="false" />
  </ComplexType>
  <ComplexType Name="FieldWithUrl" BaseType="Ui.Field">
    <Property Name="Url" Type="Edm.String" />
  </ComplexType>
  <ComplexType Name="Restriction">
    <Property Name="Allowed" Type="B.Kind" />
    <Property Name="Missing" Type="Elsewhere.Thing" />
  </ComplexType>
</Schema>
XML, <<<'XML'
<edmx:Reference Uri="https://example.test/Base.xml">
  <edmx:Include Namespace="test.Base.v1" Alias="B" />
</edmx:Reference>
XML));

    $model->load(vocabularyEntry('test.Base.v1', 'Base'), vocabularyXpath(<<<'XML'
<Schema xmlns="http://docs.oasis-open.org/odata/ns/edm" Namespace="test.Base.v1" Alias="Base">
  <TypeDefinition Name="Toggle" UnderlyingType="Edm.Boolean" />
  <TypeDefinition Name="Kind" UnderlyingType="Edm.String" />
  <ComplexType Name="Labelled" Abstract="true">
    <Property Name="Label" Type="Edm.String" />
  </ComplexType>
</Schema>
XML));

    return $model;
}

it('qualifies a reference with the alias its own document declares', function () {
    $model = loadedTypeModel();

    expect($model->qualify('Ui.Field', 'test.Ui.v1'))->toBe('test.Ui.v1.Field')
        ->and($model->qualify('B.Labelled', 'test.Ui.v1'))->toBe('test.Base.v1.Labelled')
        ->and($model->qualify('Collection(Ui.Field)', 'test.Ui.v1'))->toBe('Collection(test.Ui.v1.Field)')
        ->and($model->qualify('Edm.String', 'test.Ui.v1'))->toBe('Edm.String')
        // "B" is an alias in the Ui document only; the Base document calls itself "Base".
        ->and($model->qualify('B.Labelled', 'test.Base.v1'))->toBe('B.Labelled')
        ->and($model->qualify('Base.Labelled', 'test.Base.v1'))->toBe('test.Base.v1.Labelled');
});

it('keeps two types of one simple name apart', function () {
    $model = loadedTypeModel();

    expect($model->isEnumType('test.Ui.v1.Kind'))->toBeTrue()
        ->and($model->isEnumType('test.Base.v1.Kind'))->toBeFalse()
        ->and($model->resolve('test.Base.v1.Kind'))->toBe('Edm.String')
        ->and($model->ownProperties('test.Ui.v1.Restriction')[0]['type'])->toBe('test.Base.v1.Kind');
});

it('reads BaseType and Abstract and follows the tree across vocabularies', function () {
    $model = loadedTypeModel();

    expect($model->isAbstract('test.Ui.v1.FieldAbstract'))->toBeTrue()
        ->and($model->isAbstract('test.Ui.v1.Field'))->toBeFalse()
        ->and($model->baseType('test.Ui.v1.FieldAbstract'))->toBe('test.Base.v1.Labelled')
        ->and($model->ancestors('test.Ui.v1.FieldWithUrl'))->toBe([
            'test.Ui.v1.Field',
            'test.Ui.v1.FieldAbstract',
            'test.Base.v1.Labelled',
        ])
        ->and($model->derivedTypes('test.Base.v1.Labelled'))->toBe([
            'test.Ui.v1.FieldAbstract',
            'test.Ui.v1.Field',
            'test.Ui.v1.FieldWithUrl',
        ]);
});

it('lists inherited properties root first, own last', function () {
    $model = loadedTypeModel();

    expect(array_column($model->properties('test.Ui.v1.FieldWithUrl'), 'name'))
        ->toBe(['Label', 'Criticality', 'Value', 'Url'])
        ->and(array_column($model->ownProperties('test.Ui.v1.FieldWithUrl'), 'name'))->toBe(['Url'])
        ->and($model->properties('test.Ui.v1.Field')[1])->toBe([
            'name'     => 'Criticality',
            'type'     => 'test.Ui.v1.Kind',
            'nullable' => true,
        ])
        ->and($model->properties('test.Ui.v1.Field')[2]['nullable'])->toBeFalse();
});

it('resolves a type definition through a chain into another vocabulary', function () {
    expect(loadedTypeModel()->resolve('test.Ui.v1.Flag'))->toBe('Edm.Boolean');
});

it('maps a qualified name to the PHP namespace of its vocabulary', function () {
    $model = loadedTypeModel();

    expect($model->phpNamespaceOf('test.Base.v1.Labelled'))->toBe('Test\\Base')
        ->and($model->phpNamespaceOf('Elsewhere.Thing'))->toBeNull();
});

it('reports references no loaded vocabulary resolves', function () {
    expect(loadedTypeModel()->unresolvedReferences())->toBe(['test.Ui.v1.Restriction/Missing → Elsewhere.Thing']);
});

it('stops at a base type that is not loaded', function () {
    $model = new VocabularyTypeModel();
    $model->load(vocabularyEntry('test.Ui.v1', 'Ui'), vocabularyXpath(<<<'XML'
<Schema xmlns="http://docs.oasis-open.org/odata/ns/edm" Namespace="test.Ui.v1" Alias="Ui">
  <ComplexType Name="Orphan" BaseType="Gone.Base"><Property Name="A" Type="Edm.String" /></ComplexType>
</Schema>
XML));

    expect($model->ancestors('test.Ui.v1.Orphan'))->toBe([])
        ->and(array_column($model->properties('test.Ui.v1.Orphan'), 'name'))->toBe(['A'])
        ->and($model->unresolvedReferences())->toBe(['test.Ui.v1.Orphan → Gone.Base']);
});
