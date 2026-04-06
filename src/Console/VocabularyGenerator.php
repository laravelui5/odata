<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Console;

use DOMDocument;
use DOMElement;
use DOMXPath;
use LaravelUi5\OData\Edm\Vocabularies\VocabularyCatalog;
use LaravelUi5\OData\Edm\Vocabularies\VocabularyEntryInterface;

/**
 * Generates typed OData vocabulary annotation classes from live CSDL XML sources.
 *
 * This class has no dependency on any framework. It is pure PHP and can be
 * invoked from a Symfony Console command, a plain PHP script, or a test.
 *
 * For each <Term> element in a vocabulary XML the generator emits one PHP class
 * that implements TypedAnnotationInterface and uses TypedAnnotationTrait.
 * For each <ComplexType> used as a term's type it emits one supporting value class.
 *
 * Output is written to $outputRoot/{namespace path}/. Files are overwritten
 * without prompting.
 *
 * Constructor:
 *   $outputRoot — absolute path to the package's src/ directory
 *   $output     — optional callable(string $line): void for progress messages;
 *                 null silences all output
 *
 * Usage:
 *   $gen = new VocabularyGenerator(
 *       outputRoot: '/path/to/package/src',
 *       output: fn(string $line) => print($line . "\n"),
 *   );
 *   $gen->run();             // all vocabularies
 *   $gen->run('UI');         // single vocabulary by alias
 *
 * @see TypedAnnotationInterface
 * @see OData CSDL XML v4.01 §14 (Annotation)
 */
final class VocabularyGenerator
{
    // ── Mapping tables ────────────────────────────────────────────────────────

    /**
     * Maps OData AppliesTo values to PHP Attribute target constants.
     * Values absent from this table are skipped with a warning.
     *
     * @see OData CSDL XML v4.01 §14.1.2 (Applicability)
     */
    private const array ATTRIBUTE_TARGET_MAP = [
        'EntityType'         => 'Attribute::TARGET_CLASS',
        'ComplexType'        => 'Attribute::TARGET_CLASS',
        'EntitySet'          => 'Attribute::TARGET_CLASS',
        'Singleton'          => 'Attribute::TARGET_CLASS',
        'EnumType'           => 'Attribute::TARGET_CLASS',
        'TypeDefinition'     => 'Attribute::TARGET_CLASS',
        'EntityContainer'    => 'Attribute::TARGET_CLASS',
        'Property'           => 'Attribute::TARGET_PROPERTY',
        'NavigationProperty' => 'Attribute::TARGET_PROPERTY',
        'Parameter'          => 'Attribute::TARGET_PARAMETER',
        'EnumMember'         => 'Attribute::TARGET_CLASS_CONSTANT',
        'Action'             => 'Attribute::TARGET_METHOD',
        'Function'           => 'Attribute::TARGET_METHOD',
        // 'Term' intentionally absent — not applicable in PHP
    ];

    /**
     * Maps OData AppliesTo values to the PHP interface class-string used in
     * the generated APPLIES_TO constant.
     *
     * @see OData CSDL XML v4.01 §14.1.2 (Applicability)
     */
    private const array APPLIES_TO_INTERFACE_MAP = [
        'EntityType'         => 'LaravelUi5\\OData\\Edm\\Contracts\\Type\\EntityTypeInterface',
        'ComplexType'        => 'LaravelUi5\\OData\\Edm\\Contracts\\Type\\ComplexTypeInterface',
        'EnumType'           => 'LaravelUi5\\OData\\Edm\\Contracts\\Container\\EnumTypeInterface',
        'EnumMember'         => 'LaravelUi5\\OData\\Edm\\Contracts\\Container\\EnumMemberInterface',
        'TypeDefinition'     => 'LaravelUi5\\OData\\Edm\\Contracts\\Type\\TypeDefinitionInterface',
        'Property'           => 'LaravelUi5\\OData\\Edm\\Contracts\\Property\\PropertyInterface',
        'NavigationProperty' => 'LaravelUi5\\OData\\Edm\\Contracts\\Property\\NavigationPropertyInterface',
        'EntitySet'          => 'LaravelUi5\\OData\\Edm\\Contracts\\Container\\EntitySetInterface',
        'Singleton'          => 'LaravelUi5\\OData\\Edm\\Contracts\\Container\\SingletonInterface',
        'FunctionImport'     => 'LaravelUi5\\OData\\Edm\\Contracts\\Container\\FunctionImportInterface',
        'EntityContainer'    => 'LaravelUi5\\OData\\Edm\\Contracts\\Container\\EntityContainerInterface',
        'Parameter'          => 'LaravelUi5\\OData\\Edm\\Contracts\\FunctionParameterInterface',
        'Action'             => 'LaravelUi5\\OData\\Edm\\Contracts\\FunctionInterface',
        'Function'           => 'LaravelUi5\\OData\\Edm\\Contracts\\FunctionInterface',
        'Schema'             => 'LaravelUi5\\OData\\Edm\\Contracts\\SchemaInterface',
    ];

    // ── Primitive type mappings ───────────────────────────────────────────────

    /** Maps OData primitive type names to PHP scalar types. */
    private const array PRIMITIVE_PHP_TYPES = [
        'Edm.String'                 => 'string',
        'Edm.Guid'                   => 'string',
        'Edm.Duration'               => 'string',
        'Edm.Date'                   => 'string',
        'Edm.TimeOfDay'              => 'string',
        'Edm.DateTimeOffset'         => 'string',
        'Edm.Binary'                 => 'string',
        'Edm.AnnotationPath'         => 'string',
        'Edm.PropertyPath'           => 'string',
        'Edm.NavigationPropertyPath' => 'string',
        'Edm.ModelElementPath'       => 'string',
        'Edm.Boolean'                => 'bool',
        'Edm.Byte'                   => 'int',
        'Edm.SByte'                  => 'int',
        'Edm.Int16'                  => 'int',
        'Edm.Int32'                  => 'int',
        'Edm.Int64'                  => 'int',
        'Edm.Decimal'                => 'float',
        'Edm.Double'                 => 'float',
        'Edm.Single'                 => 'float',
    ];

    /** Maps OData primitive types to ConstantAnnotationValue kind strings. */
    private const array PRIMITIVE_KINDS = [
        'Edm.String'                 => 'String',
        'Edm.Guid'                   => 'Guid',
        'Edm.Duration'               => 'Duration',
        'Edm.Date'                   => 'Date',
        'Edm.TimeOfDay'              => 'TimeOfDay',
        'Edm.DateTimeOffset'         => 'DateTimeOffset',
        'Edm.Binary'                 => 'Binary',
        'Edm.AnnotationPath'         => 'AnnotationPath',
        'Edm.PropertyPath'           => 'PropertyPath',
        'Edm.NavigationPropertyPath' => 'NavigationPropertyPath',
        'Edm.ModelElementPath'       => 'ModelElementPath',
        'Edm.Boolean'                => 'Boolean',
        'Edm.Byte'                   => 'Integer',
        'Edm.SByte'                  => 'Integer',
        'Edm.Int16'                  => 'Integer',
        'Edm.Int32'                  => 'Integer',
        'Edm.Int64'                  => 'Integer',
        'Edm.Decimal'                => 'Decimal',
        'Edm.Double'                 => 'Float',
        'Edm.Single'                 => 'Float',
    ];

    // ── Per-vocabulary mutable state ──────────────────────────────────────────

    /** ComplexType definitions keyed by simple name, accumulated across all vocabulary passes. */
    private array $complexTypes = [];

    /** TypeDefinition underlying types keyed by simple name, accumulated across all passes. */
    private array $typeDefinitions = [];

    /**
     * EnumType definitions keyed by simple name, accumulated across all passes.
     * Each entry: {isFlags: bool, members: list<{name: string, value: int}>, phpNamespace: string}
     */
    private array $enumTypes = [];

    /** Terms whose buildAnnotationValue() body could not be fully resolved. */
    private array $stubs = [];

    // ── Construction ──────────────────────────────────────────────────────────

    /**
     * @param string        $outputRoot Absolute path to the package src/ directory
     * @param \Closure|null $output     Optional fn(string $line): void for progress output
     */
    public function __construct(
        private readonly string   $outputRoot,
        private readonly ?\Closure $output = null,
    ) {}

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Runs the generator for the given vocabulary alias, or for all vocabularies
     * in VocabularyCatalog::default() when $alias is null.
     *
     * Returns true on success, false when $alias is given but not found.
     */
    public function run(?string $alias = null): bool
    {
        $this->stubs = [];

        $catalog = VocabularyCatalog::default();

        if ($alias !== null) {
            foreach ($catalog->getEntries() as $entry) {
                if ($entry->getAlias() === $alias) {
                    $this->processEntry($entry);
                    $this->emitStubSummary();
                    return true;
                }
            }
            $this->emit("ERROR: Unknown vocabulary alias: {$alias}");
            return false;
        }

        foreach ($catalog->getEntries() as $entry) {
            $this->processEntry($entry);
        }
        $this->emitStubSummary();
        return true;
    }

    // ── Per-vocabulary processing ─────────────────────────────────────────────

    private function processEntry(VocabularyEntryInterface $entry): void
    {
        $this->emit("Processing {$entry->getAlias()} ({$entry->getNamespace()})...");

        $xml = $this->fetchXml($entry->getUri());
        if ($xml === null) {
            $this->emit("  ERROR: Failed to fetch XML from {$entry->getUri()}");
            return;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        if (!$dom->loadXML($xml)) {
            $this->emit("  ERROR: Failed to parse XML from {$entry->getUri()}");
            return;
        }
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('edm', 'http://docs.oasis-open.org/odata/ns/edm');

        // Pass 0a: collect TypeDefinition underlying types.
        $typeDefNodes = $xpath->query('//edm:Schema/edm:TypeDefinition');
        if ($typeDefNodes !== false) {
            foreach ($typeDefNodes as $node) {
                if (!($node instanceof DOMElement)) {
                    continue;
                }
                $name = $node->getAttribute('Name');
                $underlying = $node->getAttribute('UnderlyingType') ?: 'Edm.String';
                if ($name !== '') {
                    $this->typeDefinitions[$name] = $underlying;
                }
            }
        }

        // Pass 0b: collect EnumType definitions.
        $enumTypeNodes = $xpath->query('//edm:Schema/edm:EnumType');
        if ($enumTypeNodes !== false) {
            foreach ($enumTypeNodes as $node) {
                if (!($node instanceof DOMElement)) {
                    continue;
                }
                $name = $node->getAttribute('Name');
                if ($name === '') {
                    continue;
                }
                $isFlags = $node->getAttribute('IsFlags') === 'true';
                $members = [];
                $memberNodes = $xpath->query('edm:Member', $node);
                if ($memberNodes !== false) {
                    foreach ($memberNodes as $memberNode) {
                        if (!($memberNode instanceof DOMElement)) {
                            continue;
                        }
                        $mName  = $memberNode->getAttribute('Name');
                        $mValue = $memberNode->getAttribute('Value');
                        if ($mName !== '') {
                            $members[] = [
                                'name'  => $mName,
                                'value' => $mValue !== '' ? (int) $mValue : count($members),
                            ];
                        }
                    }
                }
                $this->enumTypes[$name] = [
                    'isFlags'      => $isFlags,
                    'members'      => $members,
                    'phpNamespace' => $entry->getPhpNamespace(),
                ];
            }
        }

        // Pass 1: collect all ComplexType definitions so term generation can reference them.
        $complexTypeNodes = $xpath->query('//edm:Schema/edm:ComplexType');
        if ($complexTypeNodes !== false) {
            foreach ($complexTypeNodes as $node) {
                if (!($node instanceof DOMElement)) {
                    continue;
                }
                $name = $node->getAttribute('Name');
                if ($name !== '') {
                    $this->complexTypes[$name] = $this->parseComplexTypeProperties($node, $xpath);
                }
            }
        }

        $outputDir = $this->outputDirForEntry($entry);

        // Pass 0c: generate EnumType PHP files.
        if ($enumTypeNodes !== false) {
            foreach ($enumTypeNodes as $node) {
                if (!($node instanceof DOMElement)) {
                    continue;
                }
                $file = $this->generateEnumTypeClass($node, $entry, $xpath, $outputDir);
                if ($file !== null) {
                    $this->emit("  + {$file}");
                }
            }
        }

        // Pass 2: generate ComplexType supporting classes.
        if ($complexTypeNodes !== false) {
            foreach ($complexTypeNodes as $node) {
                if (!($node instanceof DOMElement)) {
                    continue;
                }
                $file = $this->generateComplexTypeClass($node, $entry, $xpath, $outputDir);
                if ($file !== null) {
                    $this->emit("  + {$file}");
                }
            }
        }

        // Pass 3: generate Term classes.
        $termNodes = $xpath->query('//edm:Schema/edm:Term');
        if ($termNodes !== false) {
            foreach ($termNodes as $node) {
                if (!($node instanceof DOMElement)) {
                    continue;
                }
                $file = $this->generateTermClass($node, $entry, $xpath, $outputDir);
                if ($file !== null) {
                    $this->emit("  + {$file}");
                }
            }
        }
    }

    // ── XML fetching ──────────────────────────────────────────────────────────

    private function fetchXml(string $uri): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method'     => 'GET',
                'user_agent' => 'LaravelUi5/OData Vocabulary Generator',
                'timeout'    => 30,
            ],
        ]);

        $result = @file_get_contents($uri, false, $context);
        return $result !== false ? $result : null;
    }

    // ── Output path resolution ────────────────────────────────────────────────

    /**
     * Derives the absolute output directory for a vocabulary entry.
     *
     * "LaravelUi5\OData\Vocabularies\Core\V1" → "{outputRoot}/Vocabularies/Core/V1/"
     */
    private function outputDirForEntry(VocabularyEntryInterface $entry): string
    {
        $phpNamespace = $entry->getPhpNamespace();
        $prefix       = 'LaravelUi5\\OData\\';
        $relative     = str_starts_with($phpNamespace, $prefix)
            ? substr($phpNamespace, strlen($prefix))
            : $phpNamespace;

        $dir = $this->outputRoot . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    // ── ComplexType parsing ───────────────────────────────────────────────────

    /**
     * @return list<array{name: string, type: string, nullable: bool}>
     */
    private function parseComplexTypeProperties(DOMElement $node, DOMXPath $xpath): array
    {
        $properties = [];
        $propNodes  = $xpath->query('edm:Property', $node);
        if ($propNodes === false) {
            return $properties;
        }
        foreach ($propNodes as $propNode) {
            if (!($propNode instanceof DOMElement)) {
                continue;
            }
            $properties[] = [
                'name'     => $propNode->getAttribute('Name'),
                'type'     => $propNode->getAttribute('Type'),
                'nullable' => strtolower($propNode->getAttribute('Nullable') ?: 'true') !== 'false',
            ];
        }
        return $properties;
    }

    // ── ComplexType class generation ──────────────────────────────────────────

    private function generateComplexTypeClass(
        DOMElement $node,
        VocabularyEntryInterface $entry,
        DOMXPath $xpath,
        string $outputDir,
    ): ?string {
        $name = $node->getAttribute('Name');
        if ($name === '') {
            return null;
        }

        $properties  = $this->complexTypes[$name] ?? [];
        $description = $this->extractDescription($node, $xpath);

        $code     = $this->buildComplexTypeClassCode($name, $properties, $entry->getPhpNamespace(), $description);
        $filePath = $outputDir . DIRECTORY_SEPARATOR . $name . '.php';
        file_put_contents($filePath, $code);
        return $filePath;
    }

    /**
     * @param list<array{name: string, type: string, nullable: bool}> $properties
     */
    private function buildComplexTypeClassCode(
        string $name,
        array $properties,
        string $phpNs,
        string $description,
    ): string {
        $ctorParams = $this->buildComplexTypeCtorParams($properties);
        $docblock   = $description !== '' ? "/**\n * {$description}\n */\n" : '';

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$phpNs};

{$docblock}final readonly class {$name}
{
    public function __construct(
{$ctorParams}    ) {}
}

PHP;
    }

    /**
     * @param list<array{name: string, type: string, nullable: bool}> $properties
     */
    private function buildComplexTypeCtorParams(array $properties): string
    {
        if ($properties === []) {
            return '';
        }

        // PHP requires required (non-nullable, no default) params before optional ones.
        // Stable-sort: non-nullable properties first, nullable (optional) properties last.
        $required = array_filter($properties, fn($p) => !$p['nullable']);
        $optional = array_filter($properties, fn($p) => $p['nullable']);
        $sorted   = [...array_values($required), ...array_values($optional)];

        $lines = [];
        foreach ($sorted as $prop) {
            $phpType  = $this->phpTypeForOdataType($prop['type']);
            // `mixed` already includes null — never write `?mixed`
            $nullable = ($prop['nullable'] && $phpType !== 'mixed') ? '?' : '';
            $default  = $prop['nullable'] ? ' = null' : '';
            $propName = lcfirst($prop['name']);
            $lines[]  = "        public readonly {$nullable}{$phpType} \${$propName}{$default},";
        }
        return implode("\n", $lines) . "\n";
    }

    // ── Term class generation ─────────────────────────────────────────────────

    private function generateTermClass(
        DOMElement $node,
        VocabularyEntryInterface $entry,
        DOMXPath $xpath,
        string $outputDir,
    ): ?string {
        $name      = $node->getAttribute('Name');
        $rawType   = $node->getAttribute('Type') ?: 'Edm.Boolean';
        $appliesto = trim($node->getAttribute('AppliesTo'));
        $nullable  = strtolower($node->getAttribute('Nullable') ?: 'true') !== 'false';

        if ($name === '') {
            return null;
        }

        $description     = $this->extractDescription($node, $xpath);
        $fullyQualified  = $entry->getNamespace() . '.' . $name;
        $appliesToValues = $appliesto !== '' ? preg_split('/\s+/', $appliesto) : [];

        $isAttribute = $this->isAttributeCandidate($rawType, $appliesToValues, $entry->getNamespace());
        $attrTargets = $isAttribute ? $this->resolveAttributeTargets($appliesToValues) : null;

        // Track terms whose buildAnnotationValue() body cannot be fully resolved.
        if ($rawType !== 'Edm.Boolean' && $rawType !== '') {
            $isCol        = str_starts_with($rawType, 'Collection(');
            $inner        = $isCol ? substr($rawType, 11, -1) : $rawType;
            $isCplx       = $this->isComplexType($inner);
            $resolvedInner = $this->resolveType($inner);
            $isTypeDef    = $resolvedInner !== $inner;
            $isEnm        = !$isCplx && $this->isEnumType($inner);
            $isPrim       = !$isCplx && isset(self::PRIMITIVE_PHP_TYPES[$resolvedInner]);

            if ($isCplx && $this->getComplexTypeProperties($inner) === []) {
                $this->stubs[] = "[{$entry->getAlias()}] {$fullyQualified}: unresolved complex type '{$inner}'";
            } elseif (!$isPrim && !$isCol && !$isCplx && !$isEnm && !$isTypeDef && $inner !== 'Edm.PrimitiveType') {
                $this->stubs[] = "[{$entry->getAlias()}] {$fullyQualified}: unknown type '{$rawType}' — mapped to mixed";
            }
        }

        $code = $this->buildTermClassCode(
            name:            $name,
            fullyQualified:  $fullyQualified,
            rawType:         $rawType,
            nullable:        $nullable,
            appliesToValues: $appliesToValues,
            phpNs:           $entry->getPhpNamespace(),
            description:     $description,
            attrTargets:     $attrTargets,
        );

        $filePath = $outputDir . DIRECTORY_SEPARATOR . $name . '.php';
        file_put_contents($filePath, $code);
        return $filePath;
    }

    /**
     * @param list<string>      $appliesToValues
     * @param list<string>|null $attrTargets
     */
    private function buildTermClassCode(
        string $name,
        string $fullyQualified,
        string $rawType,
        bool $nullable,
        array $appliesToValues,
        string $phpNs,
        string $description,
        ?array $attrTargets,
    ): string {
        $isCollection = str_starts_with($rawType, 'Collection(');
        $innerType    = $isCollection ? substr($rawType, 11, -1) : $rawType;
        $isComplex    = $this->isComplexType($innerType);
        $isPrimitive  = !$isComplex && isset(self::PRIMITIVE_PHP_TYPES[$innerType]);

        // ── Use statements ────────────────────────────────────────────────────
        $uses = [
            'LaravelUi5\\OData\\Edm\\Contracts\\Annotation\\AnnotationValueInterface',
            'LaravelUi5\\OData\\Edm\\Contracts\\AnnotationTargetInterface',
            'LaravelUi5\\OData\\Vocabularies\\TypedAnnotationInterface',
            'LaravelUi5\\OData\\Vocabularies\\TypedAnnotationTrait',
        ];

        if ($attrTargets !== null) {
            $uses[] = 'Attribute';
        }
        // ConstantAnnotationValue is needed for all non-boolean, non-marker types.
        // TypeDefinitions resolving to Edm.Boolean (e.g. Core.Tag) also return null — exclude them.
        $resolvedInner = $this->resolveType($innerType);
        $isMarker      = ($rawType === 'Edm.Boolean' || $rawType === '')
            || (!$isCollection && $resolvedInner === 'Edm.Boolean');
        if (!$isMarker) {
            $uses[] = 'LaravelUi5\\OData\\Edm\\Annotation\\ConstantAnnotationValue';
        }
        if ($isCollection) {
            $uses[] = 'LaravelUi5\\OData\\Edm\\Annotation\\CollectionAnnotationValue';
        }
        if ($isComplex) {
            $uses[] = 'LaravelUi5\\OData\\Edm\\Annotation\\RecordAnnotationValue';
            $uses[] = 'LaravelUi5\\OData\\Edm\\Annotation\\PropertyValue';
        }
        // Edm.PrimitiveType terms require PrimitiveTypeEnum in the constructor.
        if ($innerType === 'Edm.PrimitiveType') {
            $uses[] = 'LaravelUi5\\OData\\Edm\\Contracts\\Container\\PrimitiveTypeEnum';
        }

        // Enum type from a different PHP namespace needs a use import.
        if (!$isComplex && $this->isEnumType($innerType)) {
            $simpleName  = $this->simpleTypeName($innerType);
            $enumNs      = $this->enumTypes[$simpleName]['phpNamespace'] ?? $phpNs;
            if ($enumNs !== $phpNs) {
                $uses[] = $enumNs . '\\' . $simpleName;
            }
        }
        foreach ($appliesToValues as $av) {
            if (isset(self::APPLIES_TO_INTERFACE_MAP[$av])) {
                $uses[] = self::APPLIES_TO_INTERFACE_MAP[$av];
            }
        }
        $uses = array_values(array_unique($uses));
        sort($uses);
        $usesBlock = implode("\n", array_map(fn($u) => "use {$u};", $uses));

        // ── #[Attribute] line ─────────────────────────────────────────────────
        $attributeLine = '';
        if ($attrTargets !== null) {
            $flags         = $attrTargets !== [] ? implode(' | ', $attrTargets) : '';
            $attributeLine = $flags !== '' ? "#[Attribute({$flags})]\n" : "#[Attribute]\n";
        }

        // ── APPLIES_TO constant ───────────────────────────────────────────────
        $appliesToLines = [];
        foreach ($appliesToValues as $av) {
            if (isset(self::APPLIES_TO_INTERFACE_MAP[$av])) {
                $fqcn             = self::APPLIES_TO_INTERFACE_MAP[$av];
                $short            = substr($fqcn, strrpos($fqcn, '\\') + 1);
                $appliesToLines[] = "        {$short}::class,";
            }
        }
        $appliesToBlock = $appliesToLines !== []
            ? "\n" . implode("\n", $appliesToLines) . "\n    "
            : '';

        // ── Constructor + buildAnnotationValue ────────────────────────────────
        [$ctorParams, $ctorDocblock] = $this->buildTermCtorParams($rawType, $nullable, $innerType, $isCollection, $isComplex);
        $buildBody = $this->buildAnnotationValueBody($rawType, $innerType, $isCollection, $isComplex, $nullable);

        // ── Class docblock ────────────────────────────────────────────────────
        $classDocParts   = $description !== '' ? [" * {$description}", ' * @see TypedAnnotationInterface'] : [' * @see TypedAnnotationInterface'];
        $classDoc        = "/**\n" . implode("\n", $classDocParts) . "\n */\n";
        $ctorDocblockStr = $ctorDocblock !== '' ? "    {$ctorDocblock}\n" : '';

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$phpNs};

{$usesBlock}

{$classDoc}{$attributeLine}final readonly class {$name} implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = '{$fullyQualified}';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [{$appliesToBlock}];

    {$ctorDocblockStr}public function __construct(
{$ctorParams}        public readonly ?string \$qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        {$buildBody}
    }
}

PHP;
    }

    /**
     * @return array{0: string, 1: string} [ctorParams, ctorDocblock]
     */
    private function buildTermCtorParams(
        string $rawType,
        bool $nullable,
        string $innerType,
        bool $isCollection,
        bool $isComplex,
    ): array {
        $docblock = '';

        if ($rawType === 'Edm.Boolean' || $rawType === '') {
            return ['', $docblock];
        }

        // Resolve TypeDefinitions (e.g. Core.Tag → Edm.Boolean).
        $resolvedInner = $this->resolveType($innerType);

        // TypeDefinition resolving to Edm.Boolean is a marker — no value parameter.
        if (!$isCollection && $resolvedInner === 'Edm.Boolean') {
            return ['', $docblock];
        }

        if ($isCollection) {
            if ($this->isEnumType($innerType)) {
                $innerPhpType = $this->simpleTypeName($innerType);
            } else {
                $innerPhpType = $this->phpTypeForOdataType($resolvedInner);
            }
            $docblock = "/** @param list<{$innerPhpType}> \$value */";
            return ["        public readonly array \$value = [],\n", $docblock];
        }

        if ($isComplex) {
            $properties = $this->getComplexTypeProperties($innerType);
            if ($properties === []) {
                // Unknown complex type — `mixed` subsumes null, so never `?mixed`
                $default = $nullable ? ' = null' : '';
                return ["        public readonly mixed \$value{$default},\n", $docblock];
            }
            // Sort: required (non-nullable) params before optional (nullable) params.
            $required = array_filter($properties, fn($p) => !$p['nullable']);
            $optional = array_filter($properties, fn($p) => $p['nullable']);
            $sorted   = [...array_values($required), ...array_values($optional)];
            $lines    = [];
            foreach ($sorted as $prop) {
                $phpType  = $this->phpTypeForOdataType($prop['type']);
                $null     = ($prop['nullable'] && $phpType !== 'mixed') ? '?' : '';
                $default  = $prop['nullable'] ? ' = null' : '';
                $propName = lcfirst($prop['name']);
                $lines[]  = "        public readonly {$null}{$phpType} \${$propName}{$default},";
            }
            return [implode("\n", $lines) . "\n", $docblock];
        }

        // Enum type — use the PHP enum class name as the type hint.
        if ($this->isEnumType($innerType)) {
            $simpleName = $this->simpleTypeName($innerType);
            $nullPfx    = $nullable ? '?' : '';
            $default    = $nullable ? ' = null' : '';
            return ["        public readonly {$nullPfx}{$simpleName} \$value{$default},\n", $docblock];
        }

        // Edm.PrimitiveType — the annotator must declare the concrete primitive kind.
        // PrimitiveTypeEnum is a required first parameter so the kind is never ambiguous.
        if ($innerType === 'Edm.PrimitiveType') {
            $default = $nullable ? ' = null' : '';
            return [
                "        public readonly PrimitiveTypeEnum \$type,\n"
                . "        public readonly mixed \$value{$default},\n",
                $docblock,
            ];
        }

        // Primitive type (including TypeDefinition-resolved).
        $phpType = self::PRIMITIVE_PHP_TYPES[$resolvedInner] ?? 'mixed';
        // `mixed` subsumes null — never write `?mixed`
        $nullPfx = ($nullable && $phpType !== 'mixed') ? '?' : '';
        $default = $nullable ? ' = null' : '';
        return ["        public readonly {$nullPfx}{$phpType} \$value{$default},\n", $docblock];
    }

    private function buildAnnotationValueBody(
        string $rawType,
        string $innerType,
        bool $isCollection,
        bool $isComplex,
        bool $nullable,
    ): string {
        if ($rawType === 'Edm.Boolean' || $rawType === '') {
            return 'return null;';
        }

        // Resolve TypeDefinitions (e.g. Core.Tag → Edm.Boolean).
        $resolvedInner = $this->resolveType($innerType);

        // TypeDefinition resolving to Edm.Boolean → marker, no value emitted.
        if (!$isCollection && $resolvedInner === 'Edm.Boolean') {
            return 'return null;';
        }

        if ($isCollection) {
            if ($this->isEnumType($innerType)) {
                return "return new CollectionAnnotationValue(\n            ...array_map(\n                static fn(\$v) => new ConstantAnnotationValue('Integer', (string) \$v->value),\n                \$this->value,\n            ),\n        );";
            }
            $kind = self::PRIMITIVE_KINDS[$resolvedInner] ?? 'String';
            return "return new CollectionAnnotationValue(\n            ...array_map(\n                static fn(\$v) => new ConstantAnnotationValue('{$kind}', (string) \$v),\n                \$this->value,\n            ),\n        );";
        }

        if ($isComplex) {
            $properties = $this->getComplexTypeProperties($innerType);
            if ($properties === []) {
                return 'return null;';
            }
            $pvLines = [];
            foreach ($properties as $prop) {
                $propName  = lcfirst($prop['name']);
                $kind      = self::PRIMITIVE_KINDS[$prop['type']] ?? 'String';
                $pvLines[] = "            new PropertyValue('{$prop['name']}', new ConstantAnnotationValue('{$kind}', (string) \$this->{$propName})),";
            }
            $pvBlock = implode("\n", $pvLines);
            return "return new RecordAnnotationValue(\n            '{$innerType}',\n{$pvBlock}\n        );";
        }

        // Enum type — use the PHP enum backing value.
        if ($this->isEnumType($innerType)) {
            if ($nullable) {
                return "if (\$this->value === null) {\n            return null;\n        }\n        return new ConstantAnnotationValue('Integer', (string) \$this->value->value);";
            }
            return "return new ConstantAnnotationValue('Integer', (string) \$this->value->value);";
        }

        // Edm.PrimitiveType — dispatch on the annotator-declared PrimitiveTypeEnum.
        if ($innerType === 'Edm.PrimitiveType') {
            $match = "return match (\$this->type) {\n"
                . "            PrimitiveTypeEnum::Byte,\n"
                . "            PrimitiveTypeEnum::SByte,\n"
                . "            PrimitiveTypeEnum::Int16,\n"
                . "            PrimitiveTypeEnum::Int32,\n"
                . "            PrimitiveTypeEnum::Int64      => new ConstantAnnotationValue('Integer', (string) \$this->value),\n"
                . "            PrimitiveTypeEnum::Decimal    => new ConstantAnnotationValue('Decimal', (string) \$this->value),\n"
                . "            PrimitiveTypeEnum::Double,\n"
                . "            PrimitiveTypeEnum::Single     => new ConstantAnnotationValue('Float', (string) \$this->value),\n"
                . "            PrimitiveTypeEnum::Boolean    => new ConstantAnnotationValue('Boolean', \$this->value ? 'true' : 'false'),\n"
                . "            PrimitiveTypeEnum::Date       => new ConstantAnnotationValue('Date', (string) \$this->value),\n"
                . "            PrimitiveTypeEnum::DateTimeOffset => new ConstantAnnotationValue('DateTimeOffset', (string) \$this->value),\n"
                . "            PrimitiveTypeEnum::TimeOfDay  => new ConstantAnnotationValue('TimeOfDay', (string) \$this->value),\n"
                . "            PrimitiveTypeEnum::Duration   => new ConstantAnnotationValue('Duration', (string) \$this->value),\n"
                . "            PrimitiveTypeEnum::Guid       => new ConstantAnnotationValue('Guid', (string) \$this->value),\n"
                . "            default                       => new ConstantAnnotationValue('String', (string) \$this->value),\n"
                . "        };";
            if ($nullable) {
                return "if (\$this->value === null) {\n            return null;\n        }\n        " . $match;
            }
            return $match;
        }

        // Primitive type (including TypeDefinition-resolved).
        $kind = self::PRIMITIVE_KINDS[$resolvedInner] ?? 'String';
        if ($resolvedInner === 'Edm.Boolean') {
            return "return new ConstantAnnotationValue('{$kind}', \$this->value ? 'true' : 'false');";
        }
        if ($nullable) {
            return "if (\$this->value === null) {\n            return null;\n        }\n        return new ConstantAnnotationValue('{$kind}', (string) \$this->value);";
        }
        return "return new ConstantAnnotationValue('{$kind}', (string) \$this->value);";
    }

    // ── Attribute candidate detection ─────────────────────────────────────────

    /**
     * Returns true when the term qualifies as a PHP #[Attribute].
     *
     * Three conditions must all hold:
     *   1. Type is not a Container-path type (EntitySetPath / NavigationPropertyPath).
     *   2. AppliesTo contains at least one value in ATTRIBUTE_TARGET_MAP.
     *   3. The vocabulary is not Org.OData.Capabilities.V1.
     *
     * @param list<string> $appliesToValues
     */
    private function isAttributeCandidate(string $rawType, array $appliesToValues, string $odataNamespace): bool
    {
        if ($odataNamespace === 'Org.OData.Capabilities.V1') {
            return false;
        }
        if ($rawType === 'Edm.EntitySetPath') {
            return false;
        }

        $innerType = str_starts_with($rawType, 'Collection(') ? substr($rawType, 11, -1) : $rawType;
        if (isset($this->complexTypes[$innerType])) {
            foreach ($this->complexTypes[$innerType] as $prop) {
                if ($prop['type'] === 'Edm.NavigationPropertyPath') {
                    return false;
                }
            }
        }

        foreach ($appliesToValues as $av) {
            if (isset(self::ATTRIBUTE_TARGET_MAP[$av])) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param  list<string> $appliesToValues
     * @return list<string> deduplicated PHP constant expressions
     */
    private function resolveAttributeTargets(array $appliesToValues): array
    {
        $targets = [];
        foreach ($appliesToValues as $av) {
            if (!isset(self::ATTRIBUTE_TARGET_MAP[$av])) {
                if ($av !== 'Term' && $av !== '') {
                    $this->emit("  WARN: Unknown AppliesTo value '{$av}' — skipped in Attribute target.");
                }
                continue;
            }
            $targets[] = self::ATTRIBUTE_TARGET_MAP[$av];
        }
        return array_values(array_unique($targets));
    }

    // ── XML helpers ───────────────────────────────────────────────────────────

    private function extractDescription(DOMElement $node, DOMXPath $xpath): string
    {
        $annotations = $xpath->query(
            'edm:Annotation[@Term="Core.Description" or @Term="Org.OData.Core.V1.Description"]',
            $node,
        );
        if ($annotations === false || $annotations->length === 0) {
            return '';
        }
        /** @var DOMElement $ann */
        $ann = $annotations->item(0);
        $str = $ann->getAttribute('String');
        if ($str !== '') {
            return $str;
        }
        $stringNodes = $xpath->query('edm:String', $ann);
        if ($stringNodes !== false && $stringNodes->length > 0) {
            return trim($stringNodes->item(0)->textContent);
        }
        return '';
    }

    // ── Type helpers ──────────────────────────────────────────────────────────

    private function phpTypeForOdataType(string $odataType): string
    {
        if (str_starts_with($odataType, 'Collection(')) {
            return 'array';
        }
        return self::PRIMITIVE_PHP_TYPES[$this->resolveType($odataType)] ?? 'mixed';
    }

    private function isComplexType(string $type): bool
    {
        if (str_starts_with($type, 'Edm.')) {
            return false;
        }
        $simpleName = str_contains($type, '.') ? substr($type, strrpos($type, '.') + 1) : $type;
        return array_key_exists($simpleName, $this->complexTypes);
    }

    /**
     * Returns the property list for a complex type by simple name, alias-qualified name,
     * or fully qualified name. Returns [] when the type is not in the accumulated map.
     *
     * @return list<array{name: string, type: string, nullable: bool}>
     */
    private function getComplexTypeProperties(string $type): array
    {
        $simpleName = str_contains($type, '.') ? substr($type, strrpos($type, '.') + 1) : $type;
        return $this->complexTypes[$simpleName] ?? [];
    }

    /**
     * Resolves a TypeDefinition alias to its underlying OData type.
     * Returns the type unchanged if it is not a TypeDefinition.
     */
    private function resolveType(string $type): string
    {
        $simpleName = $this->simpleTypeName($type);
        return $this->typeDefinitions[$simpleName] ?? $type;
    }

    /** Returns true when $type refers to a known EnumType. */
    private function isEnumType(string $type): bool
    {
        if (str_starts_with($type, 'Edm.')) {
            return false;
        }
        return array_key_exists($this->simpleTypeName($type), $this->enumTypes);
    }

    /** Extracts the simple (unqualified) name from an OData type string. */
    private function simpleTypeName(string $type): string
    {
        return str_contains($type, '.') ? substr($type, strrpos($type, '.') + 1) : $type;
    }

    // ── EnumType class generation ─────────────────────────────────────────────

    private function generateEnumTypeClass(
        DOMElement $node,
        VocabularyEntryInterface $entry,
        DOMXPath $xpath,
        string $outputDir,
    ): ?string {
        $name = $node->getAttribute('Name');
        if ($name === '') {
            return null;
        }

        $enumInfo    = $this->enumTypes[$name] ?? null;
        if ($enumInfo === null) {
            return null;
        }

        $description = $this->extractDescription($node, $xpath);
        $code        = $this->buildEnumClassCode($name, $enumInfo, $entry->getPhpNamespace(), $description);
        $filePath    = $outputDir . DIRECTORY_SEPARATOR . $name . '.php';
        file_put_contents($filePath, $code);
        return $filePath;
    }

    private function buildEnumClassCode(
        string $name,
        array $enumInfo,
        string $phpNs,
        string $description,
    ): string {
        $docblock = $description !== '' ? "/**\n * {$description}\n */\n" : '';
        $cases    = implode("\n", array_map(
            static fn($m) => "    case {$m['name']} = {$m['value']};",
            $enumInfo['members'],
        ));
        $flagsComment = $enumInfo['isFlags'] ? "\n    // IsFlags = true" : '';

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$phpNs};

{$docblock}enum {$name}: int
{{$flagsComment}
{$cases}
}

PHP;
    }

    // ── Output ────────────────────────────────────────────────────────────────

    private function emit(string $line): void
    {
        if ($this->output !== null) {
            ($this->output)($line);
        }
    }

    private function emitStubSummary(): void
    {
        if ($this->stubs === []) {
            $this->emit('');
            $this->emit('All buildAnnotationValue() bodies fully resolved.');
            return;
        }

        $this->emit('');
        $this->emit(sprintf(
            '%d term(s) require manual implementation of buildAnnotationValue():',
            count($this->stubs),
        ));
        foreach ($this->stubs as $stub) {
            $this->emit("  ! {$stub}");
        }
    }
}
