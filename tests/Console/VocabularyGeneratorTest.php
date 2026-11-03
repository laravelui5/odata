<?php

declare(strict_types=1);

use LaravelUi5\OData\Console\VocabularyGenerator;

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
    </Schema>
  </edmx:DataServices>
</edmx:Edmx>
XML;

beforeEach(function () {
    $this->outputRoot = sys_get_temp_dir() . '/odata-vocabulary-generator-' . bin2hex(random_bytes(4));
    $fetcher = static fn(string $uri): string => match (basename($uri)) {
        'Org.OData.Core.V1.xml' => GENERATOR_CORE_XML,
        'Common.xml'            => GENERATOR_COMMON_XML,
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
    expect($code)->toContain('public readonly ?string $fromCore = null,')
        ->and($code)->not->toContain('?string $fromCommon');
});

it('reads every vocabulary before generating a single one', function () {
    expect($this->generator->run('Common'))->toBeTrue();

    expect(($this->read)('Common/V1/RestrictionType.php'))->toContain('?string $fromCore')
        ->and(is_file("{$this->outputRoot}/Vocabularies/Core/V1/Description.php"))->toBeFalse();
});

it('refuses an unknown alias', function () {
    expect($this->generator->run('Nope'))->toBeFalse()
        ->and($this->lines)->toContain('ERROR: Unknown vocabulary alias: Nope');
});
