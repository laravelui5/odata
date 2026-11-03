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
     * The symbolic values the standard defines for AppliesTo. A value outside this list is warned about;
     * a value in it without a PHP target (FunctionImport, Record, Annotation …) is skipped quietly.
     *
     * @see OData CSDL XML v4.01 §14.1.2 (Applicability)
     */
    private const array APPLIES_TO_VALUES = [
        'Action', 'ActionImport', 'Annotation', 'Apply', 'Cast', 'Collection', 'ComplexType', 'EntityContainer',
        'EntitySet', 'EntityType', 'EnumType', 'Function', 'FunctionImport', 'If', 'Include', 'IsOf', 'LabeledElement',
        'Member', 'NavigationProperty', 'Null', 'OnDelete', 'Parameter', 'Property', 'PropertyValue', 'Record',
        'Reference', 'ReferentialConstraint', 'ReturnType', 'Schema', 'Singleton', 'Term', 'TypeDefinition', 'UrlRef',
    ];

    /**
     * Maps OData AppliesTo values to PHP Attribute target constants.
     *
     * Only model elements a PHP declaration can carry are here. Function imports are built in code and take
     * their annotations through the constructor; annotations, records and terms cannot be annotated yet (OP43).
     *
     * @see OData CSDL XML v4.01 §14.1.2 (Applicability)
     */
    private const array ATTRIBUTE_TARGET_MAP = [
        'EntityType'         => ['Attribute::TARGET_CLASS'],
        'ComplexType'        => ['Attribute::TARGET_CLASS'],
        'EntitySet'          => ['Attribute::TARGET_CLASS'],
        'Singleton'          => ['Attribute::TARGET_CLASS'],
        'EnumType'           => ['Attribute::TARGET_CLASS'],
        'TypeDefinition'     => ['Attribute::TARGET_CLASS'],
        'EntityContainer'    => ['Attribute::TARGET_CLASS'],
        'Property'           => ['Attribute::TARGET_PROPERTY'],
        'NavigationProperty' => ['Attribute::TARGET_PROPERTY'],
        // An entity set, or a collection-valued property or navigation property.
        'Collection'         => ['Attribute::TARGET_CLASS', 'Attribute::TARGET_PROPERTY'],
        'Parameter'          => ['Attribute::TARGET_PARAMETER'],
        'Member'             => ['Attribute::TARGET_CLASS_CONSTANT'],
        'Action'             => ['Attribute::TARGET_METHOD'],
        'Function'           => ['Attribute::TARGET_METHOD'],
    ];

    /**
     * Maps OData AppliesTo values to the PHP interface class-strings used in
     * the generated APPLIES_TO constant.
     *
     * @see OData CSDL XML v4.01 §14.1.2 (Applicability)
     */
    private const array APPLIES_TO_INTERFACE_MAP = [
        'EntityType'         => ['LaravelUi5\\OData\\Edm\\Contracts\\Type\\EntityTypeInterface'],
        'ComplexType'        => ['LaravelUi5\\OData\\Edm\\Contracts\\Type\\ComplexTypeInterface'],
        'EnumType'           => ['LaravelUi5\\OData\\Edm\\Contracts\\Type\\EnumTypeInterface'],
        'Member'             => ['LaravelUi5\\OData\\Edm\\Contracts\\Type\\EnumMemberInterface'],
        'TypeDefinition'     => ['LaravelUi5\\OData\\Edm\\Contracts\\Type\\TypeDefinitionInterface'],
        'Property'           => ['LaravelUi5\\OData\\Edm\\Contracts\\Property\\PropertyInterface'],
        'NavigationProperty' => ['LaravelUi5\\OData\\Edm\\Contracts\\Property\\NavigationPropertyInterface'],
        'EntitySet'          => ['LaravelUi5\\OData\\Edm\\Contracts\\Container\\EntitySetInterface'],
        'Singleton'          => ['LaravelUi5\\OData\\Edm\\Contracts\\Container\\SingletonInterface'],
        'FunctionImport'     => ['LaravelUi5\\OData\\Edm\\Contracts\\Container\\FunctionImportInterface'],
        'EntityContainer'    => ['LaravelUi5\\OData\\Edm\\Contracts\\Container\\EntityContainerInterface'],
        'Parameter'          => ['LaravelUi5\\OData\\Edm\\Contracts\\FunctionParameterInterface'],
        'Action'             => ['LaravelUi5\\OData\\Edm\\Contracts\\FunctionInterface'],
        'Function'           => ['LaravelUi5\\OData\\Edm\\Contracts\\FunctionInterface'],
        'Schema'             => ['LaravelUi5\\OData\\Edm\\Contracts\\SchemaInterface'],
        'Collection'         => [
            'LaravelUi5\\OData\\Edm\\Contracts\\Container\\EntitySetInterface',
            'LaravelUi5\\OData\\Edm\\Contracts\\Property\\PropertyInterface',
            'LaravelUi5\\OData\\Edm\\Contracts\\Property\\NavigationPropertyInterface',
        ],
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


    // ── Run state ─────────────────────────────────────────────────────────────

    /** The types of all vocabularies in the catalog, keyed by qualified name; filled before any file is written. */
    private VocabularyTypeModel $types;

    /** Terms whose buildAnnotationValue() body could not be fully resolved. */
    private array $stubs = [];

    // ── Construction ──────────────────────────────────────────────────────────

    /**
     * @param string        $outputRoot Absolute path to the package src/ directory
     * @param \Closure|null $output     Optional fn(string $line): void for progress output
     * @param \Closure|null $fetcher    Optional fn(string $uri): ?string returning a vocabulary's CSDL XML;
     *                                  defaults to an HTTP GET of the URI
     */
    public function __construct(
        private readonly string   $outputRoot,
        private readonly ?\Closure $output = null,
        private readonly ?\Closure $fetcher = null,
    ) {
        $this->types = new VocabularyTypeModel();
    }

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
        $this->types = new VocabularyTypeModel();

        $entries = VocabularyCatalog::default()->getEntries();

        $selected = $entries;
        if ($alias !== null) {
            $selected = array_values(array_filter($entries, fn($e) => $e->getAlias() === $alias));
            if ($selected === []) {
                $this->emit("ERROR: Unknown vocabulary alias: {$alias}");
                return false;
            }
        }

        // Every vocabulary is read before any file is written, also for a single alias:
        // a type may refer to a base or property type of another vocabulary.
        $documents = [];
        foreach ($entries as $entry) {
            $xpath = $this->loadDocument($entry);
            if ($xpath !== null) {
                $this->types->load($entry, $xpath);
                $documents[$entry->getAlias()] = $xpath;
            }
        }
        foreach ($this->types->unresolvedReferences() as $reference) {
            $this->emit("WARN: Unresolved type reference {$reference} — mapped to mixed.");
        }

        foreach ($selected as $entry) {
            if (isset($documents[$entry->getAlias()])) {
                $this->processEntry($entry, $documents[$entry->getAlias()]);
            }
        }
        $this->emitStubSummary();
        return true;
    }

    // ── Per-vocabulary processing ─────────────────────────────────────────────

    private function loadDocument(VocabularyEntryInterface $entry): ?DOMXPath
    {
        $xml = $this->fetcher !== null ? ($this->fetcher)($entry->getUri()) : $this->fetchXml($entry->getUri());
        if ($xml === null) {
            $this->emit("ERROR: Failed to fetch XML from {$entry->getUri()}");
            return null;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        if (!$dom->loadXML($xml)) {
            libxml_clear_errors();
            $this->emit("ERROR: Failed to parse XML from {$entry->getUri()}");
            return null;
        }
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('edm', 'http://docs.oasis-open.org/odata/ns/edm');
        return $xpath;
    }

    private function processEntry(VocabularyEntryInterface $entry, DOMXPath $xpath): void
    {
        $this->emit("Processing {$entry->getAlias()} ({$entry->getNamespace()})...");

        $enumTypeNodes    = $xpath->query('//edm:Schema/edm:EnumType');
        $complexTypeNodes = $xpath->query('//edm:Schema/edm:ComplexType');

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

        $description = $this->extractDescription($node, $xpath);
        $code        = $this->buildComplexTypeClassCode($entry->getNamespace() . '.' . $name, $entry->getPhpNamespace(), $description);
        $filePath    = $outputDir . DIRECTORY_SEPARATOR . $name . '.php';
        file_put_contents($filePath, $code);
        return $filePath;
    }

    /**
     * A complex type as a record class in the type hierarchy: abstract for an abstract type, final for a
     * type nothing derives from. Every field, own or inherited, is a named optional parameter; the class
     * promotes the fields it declares first and hands the inherited ones to its parent.
     */
    private function buildComplexTypeClassCode(string $qualifiedName, string $phpNs, string $description): string
    {
        $name     = VocabularyTypeModel::simpleName($qualifiedName);
        $baseType = $this->types->baseType($qualifiedName);
        $hasBase  = $baseType !== null && $this->types->isComplexType($baseType);
        $imports  = new VocabularyImports($phpNs, $name);

        $parent = $hasBase
            ? $imports->reference($this->phpClassOf($baseType))
            : $imports->reference('LaravelUi5\\OData\\Edm\\Annotation\\AbstractRecord');

        $params    = [];
        $docParams = [];
        $handOver  = [];
        $fields    = [];
        foreach ($this->types->properties($qualifiedName) as $prop) {
            $property = $this->phpPropertyName($prop['name']);
            [$phpType, $docType] = $this->fieldPhpType($prop['type'], $imports);
            $own      = $this->types->declaringType($qualifiedName, $prop['name']) === $qualifiedName;
            $promote  = $own ? 'public ' : '';
            $params[] = "        {$promote}{$phpType} \${$property} = null,";
            if ($docType !== null) {
                $docParams[] = "     * @param {$docType} \${$property}";
            }
            if (!$own) {
                $handOver[] = "            {$property}: \${$property},";
            }
            $fields[] = sprintf(
                "        '%s' => ['%s', '%s', %s],",
                $prop['name'],
                $property,
                $this->fieldType($prop['type']),
                $prop['required'] ? 'true' : 'false',
            );
        }

        $ctor = '';
        $ownCount = count($params) - count($handOver);
        if ($ownCount > 0) {
            $doc  = $docParams !== [] ? "    /**\n" . implode("\n", $docParams) . "\n     */\n" : '';
            $body = $handOver !== []
                ? "\n        parent::__construct(\n" . implode("\n", $handOver) . "\n        );\n    "
                : '';
            $ctor = "\n\n{$doc}    public function __construct(\n" . implode("\n", $params) . "\n    ) {{$body}}";
        }

        $modifier = match (true) {
            $this->types->isAbstract($qualifiedName)         => 'abstract ',
            $this->types->derivedTypes($qualifiedName) === [] => 'final ',
            default                                           => '',
        };
        $fieldsBlock = $fields !== [] ? "\n" . implode("\n", $fields) . "\n    " : '';
        $docblock    = $description !== '' ? "/**\n * {$description}\n */\n" : '';
        $usesBlock   = $imports->block() !== '' ? $imports->block() . "\n\n" : '';

        return <<<PHP
<?php

declare(strict_types=1);

namespace {$phpNs};

{$usesBlock}{$docblock}{$modifier}readonly class {$name} extends {$parent}
{
    public const string TYPE = '{$qualifiedName}';

    public const array FIELDS = [{$fieldsBlock}];{$ctor}
}

PHP;
    }

    /**
     * The PHP parameter type of a record field, and its docblock type where PHP cannot say it (lists).
     * A Path stands in for every value, a model path's included (see AnnotationValueWriter).
     *
     * @return array{0: string, 1: string|null}
     */
    private function fieldPhpType(string $type, VocabularyImports $imports): array
    {
        $isCollection = str_starts_with($type, 'Collection(');
        $inner        = $isCollection ? substr($type, 11, -1) : $type;
        $resolved     = $this->resolveType($inner);
        $path         = $imports->reference('LaravelUi5\\OData\\Edm\\Annotation\\Path');

        if ($this->isComplexType($inner)) {
            $itemType = $imports->reference($this->phpClassOf($inner));
        } elseif ($this->isEnumType($inner)) {
            $itemType = $imports->reference($this->phpClassOf($inner));
            if (!$isCollection && ($this->types->enumType($inner)['isFlags'] ?? false)) {
                return ["{$itemType}|array|{$path}|null", "{$itemType}|list<{$itemType}>|{$path}|null"];
            }
        } else {
            $itemType = self::PRIMITIVE_PHP_TYPES[$resolved] ?? 'mixed';
        }

        if ($isCollection) {
            return ["array|{$path}|null", "list<{$itemType}>|{$path}|null"];
        }
        if ($itemType === 'mixed') {
            return ['mixed', null];
        }
        return ["{$itemType}|{$path}|null", null];
    }

    /** The CSDL type a record field is written with: type definitions resolved, collections kept. */
    private function fieldType(string $type): string
    {
        if (str_starts_with($type, 'Collection(')) {
            return 'Collection(' . $this->resolveType(substr($type, 11, -1)) . ')';
        }
        return $this->resolveType($type);
    }

    /**
     * The PHP name of a record field: the leading capital, or a leading acronym, lower-cased
     * (`Label` → `label`, `ID` → `id`, `IDProperty` → `idProperty`).
     */
    private function phpPropertyName(string $name): string
    {
        return (string) preg_replace_callback(
            '/^[A-Z]+(?=[A-Z][a-z]|$)|^[A-Z]/',
            static fn(array $m) => strtolower($m[0]),
            $name,
        );
    }

    /** The generated PHP class of a qualified vocabulary type. */
    private function phpClassOf(string $qualifiedName): string
    {
        $phpNs = $this->types->phpNamespaceOf($qualifiedName) ?? '';
        return $phpNs . '\\' . VocabularyTypeModel::simpleName($qualifiedName);
    }

    // ── Term class generation ─────────────────────────────────────────────────

    private function generateTermClass(
        DOMElement $node,
        VocabularyEntryInterface $entry,
        DOMXPath $xpath,
        string $outputDir,
    ): ?string {
        $name      = $node->getAttribute('Name');
        $rawType   = $this->types->qualify($node->getAttribute('Type') ?: 'Edm.Boolean', $entry->getNamespace());
        $appliesto = trim($node->getAttribute('AppliesTo'));
        // A term value may be left out when the term is nullable or has a default.
        $nullable  = strtolower($node->getAttribute('Nullable') ?: 'true') !== 'false'
            || $node->hasAttribute('DefaultValue');

        if ($name === '') {
            return null;
        }

        $description     = $this->extractDescription($node, $xpath);
        $fullyQualified  = $entry->getNamespace() . '.' . $name;
        $appliesToValues = $appliesto !== '' ? preg_split('/\s+/', $appliesto) : [];

        foreach ($appliesToValues as $av) {
            if (!in_array($av, self::APPLIES_TO_VALUES, true)) {
                $this->emit("  WARN: {$fullyQualified}: AppliesTo value '{$av}' is not defined by CSDL — skipped.");
            }
        }

        $isAttribute = $this->isAttributeCandidate($appliesToValues, $entry->getNamespace());
        $attrTargets = $isAttribute ? $this->resolveAttributeTargets($appliesToValues) : null;

        // Track terms whose type no loaded vocabulary declares: their value is written by its PHP value.
        $inner = str_starts_with($rawType, 'Collection(') ? substr($rawType, 11, -1) : $rawType;
        $written = $this->termValueType($rawType);
        if (in_array($written, ['Edm.Untyped', 'Collection(Edm.Untyped)'], true) && $this->resolveType($inner) !== 'Edm.Untyped') {
            $this->stubs[] = "[{$entry->getAlias()}] {$fullyQualified}: unknown type '{$rawType}' — mapped to mixed";
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
        $imports      = new VocabularyImports($phpNs, $name);

        // ── Use statements ────────────────────────────────────────────────────
        $imports->reference('LaravelUi5\\OData\\Edm\\Contracts\\Annotation\\AnnotationValueInterface');
        $imports->reference('LaravelUi5\\OData\\Edm\\Contracts\\AnnotationTargetInterface');
        $imports->reference('LaravelUi5\\OData\\Edm\\Contracts\\Annotation\\TypedAnnotationInterface');
        $imports->reference('LaravelUi5\\OData\\Edm\\Annotation\\TypedAnnotationTrait');
        if ($attrTargets !== null) {
            $imports->reference('Attribute');
        }

        $appliesToInterfaces = $this->appliesToInterfaces($appliesToValues);
        $appliesToLines      = [];
        foreach ($appliesToInterfaces as $fqcn) {
            $appliesToLines[] = '        ' . $imports->reference($fqcn) . '::class,';
        }
        $appliesToBlock = $appliesToLines !== []
            ? "\n" . implode("\n", $appliesToLines) . "\n    "
            : '';

        // ── #[Attribute] line ─────────────────────────────────────────────────
        $attributeLine = '';
        if ($attrTargets !== null) {
            $flags         = $attrTargets !== [] ? implode(' | ', $attrTargets) : '';
            $attributeLine = $flags !== '' ? "#[Attribute({$flags})]\n" : "#[Attribute]\n";
        }

        // ── Constructor + buildAnnotationValue ────────────────────────────────
        // A term of one concrete record takes the record's fields; an abstract record type (UI.DataFieldDefault)
        // takes a record of any type deriving from it, like a collection element does.
        if ($isComplex && !$isCollection && !$this->types->isAbstract($innerType)) {
            [$ctorParams, $ctorDocblock] = $this->recordTermCtorParams($fullyQualified, $innerType, $imports);
            $buildBody = $this->recordTermBody($innerType, $imports);
        } else {
            [$ctorParams, $ctorDocblock] = $this->buildTermCtorParams($rawType, $innerType, $isCollection, $imports);
            $buildBody = $this->buildAnnotationValueBody($fullyQualified, $rawType, $innerType, $isCollection, $nullable, $imports);
        }

        // ── Class docblock ────────────────────────────────────────────────────
        $classDocParts   = $description !== '' ? [" * {$description}", ' * @see TypedAnnotationInterface'] : [' * @see TypedAnnotationInterface'];
        $classDoc        = "/**\n" . implode("\n", $classDocParts) . "\n */\n";
        $ctorDocblockStr = $ctorDocblock !== '' ? preg_replace('/^/m', '    ', $ctorDocblock) . "\n" : '';
        $usesBlock       = $imports->block();

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

{$ctorDocblockStr}    public function __construct(
{$ctorParams}        public ?string \$qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        {$buildBody}
    }
}

PHP;
    }

    /**
     * The value parameter of a term that is not a single record. Every value is optional: a term the
     * vocabulary declares non-nullable fails when the schema is built, not at the constructor call.
     *
     * @return array{0: string, 1: string} [ctorParams, ctorDocblock]
     */
    private function buildTermCtorParams(
        string $rawType,
        string $innerType,
        bool $isCollection,
        VocabularyImports $imports,
    ): array {
        if ($this->isMarkerTerm($rawType, $innerType, $isCollection)) {
            return ['', ''];
        }

        if ($isCollection) {
            $itemType = match (true) {
                $this->isComplexType($innerType), $this->isEnumType($innerType)
                    => $imports->reference($this->phpClassOf($innerType)),
                default => self::PRIMITIVE_PHP_TYPES[$this->resolveType($innerType)] ?? 'mixed',
            };
            return ["        public array \$value = [],\n", "/** @param list<{$itemType}> \$value */"];
        }

        // Edm.PrimitiveType — the value decides its kind; $type names one the value cannot
        // (a Date or a Decimal given as a string).
        if ($innerType === 'Edm.PrimitiveType') {
            $type = $imports->reference('LaravelUi5\\OData\\Edm\\EdmPrimitiveType');
            return ["        public mixed \$value = null,\n        public ?{$type} \$type = null,\n", ''];
        }

        $known = $this->termValueType($rawType) !== 'Edm.Untyped';
        if (!$known) {
            return ["        public mixed \$value = null,\n", ''];
        }
        [$phpType, $docType] = $this->fieldPhpType($rawType, $imports);
        $docblock = $docType !== null ? "/** @param {$docType} \$value */" : '';
        return ["        public {$phpType} \$value = null,\n", $docblock];
    }

    private function buildAnnotationValueBody(
        string $fullyQualified,
        string $rawType,
        string $innerType,
        bool $isCollection,
        bool $nullable,
        VocabularyImports $imports,
    ): string {
        if ($this->isMarkerTerm($rawType, $innerType, $isCollection)) {
            return 'return null;';
        }

        $writer = $imports->reference('LaravelUi5\\OData\\Edm\\Annotation\\AnnotationValueWriter');
        $type   = $this->termValueType($rawType);

        if ($isCollection) {
            return "return {$writer}::write(\$this->value, '{$type}');";
        }

        $write = $innerType === 'Edm.PrimitiveType'
            ? "return {$writer}::write(\$this->value, \$this->type?->value ?? 'Edm.PrimitiveType');"
            : "return {$writer}::write(\$this->value, '{$type}');";

        $whenNull = $nullable
            ? 'return null;'
            : 'throw new ' . $imports->reference('LogicException') . "('The term {$fullyQualified} requires a value.');";

        return "if (\$this->value === null) {\n            {$whenNull}\n        }\n        {$write}";
    }

    /**
     * The CSDL type a term value is written with: type definitions resolved, collections kept,
     * a type no loaded vocabulary declares written as Edm.Untyped (by the PHP value).
     */
    private function termValueType(string $rawType): string
    {
        $isCollection = str_starts_with($rawType, 'Collection(');
        $inner        = $isCollection ? substr($rawType, 11, -1) : $rawType;
        $resolved     = $this->resolveType($inner);
        $known        = str_starts_with($resolved, 'Edm.') || $this->isComplexType($inner) || $this->isEnumType($inner);
        $type         = $known ? $resolved : 'Edm.Untyped';

        return $isCollection ? "Collection({$type})" : $type;
    }

    /** A Boolean term, also through a type definition (Core.Tag): the annotation has no value. */
    private function isMarkerTerm(string $rawType, string $innerType, bool $isCollection): bool
    {
        return $rawType === 'Edm.Boolean' || $rawType === ''
            || (!$isCollection && $this->resolveType($innerType) === 'Edm.Boolean');
    }

    /**
     * The fields of a term whose type is one concrete record: every field of the type, own and inherited,
     * as a named optional parameter, typed as in the record class.
     *
     * @return array{0: string, 1: string} [ctorParams, ctorDocblock]
     */
    private function recordTermCtorParams(string $fullyQualified, string $innerType, VocabularyImports $imports): array
    {
        $lines     = [];
        $docParams = [];
        foreach ($this->types->properties($innerType) as $prop) {
            $property = $this->phpPropertyName($prop['name']);
            if ($property === 'qualifier') {
                throw new \RuntimeException(
                    "The term {$fullyQualified} has a field Qualifier, which collides with the annotation qualifier.",
                );
            }
            [$phpType, $docType] = $this->fieldPhpType($prop['type'], $imports);
            $lines[] = "        public {$phpType} \${$property} = null,";
            if ($docType !== null) {
                $docParams[] = " * @param {$docType} \${$property}";
            }
        }
        $docblock = $docParams !== [] ? "/**\n" . implode("\n", $docParams) . "\n */" : '';
        return [$lines !== [] ? implode("\n", $lines) . "\n" : '', $docblock];
    }

    /**
     * The record of a term whose type is one concrete record: built through its record class, so the
     * required fields and the value writing are those of {@see \LaravelUi5\OData\Edm\Annotation\AbstractRecord}.
     */
    private function recordTermBody(string $innerType, VocabularyImports $imports): string
    {
        $class = $imports->reference($this->phpClassOf($innerType));
        $args  = array_map(
            fn(array $p) => '            ' . $this->phpPropertyName($p['name']) . ': $this->' . $this->phpPropertyName($p['name']) . ',',
            $this->types->properties($innerType),
        );
        if ($args === []) {
            return "return (new {$class}())->toRecord();";
        }
        return "return (new {$class}(\n" . implode("\n", $args) . "\n        ))->toRecord();";
    }

    // ── Attribute candidate detection ─────────────────────────────────────────

    /**
     * Returns true when the term qualifies as a PHP #[Attribute].
     *
     * Two conditions must both hold:
     *   1. AppliesTo is absent, or contains at least one value in ATTRIBUTE_TARGET_MAP.
     *   2. The vocabulary is not Org.OData.Capabilities.V1.
     *
     * A term without AppliesTo applies to every model element (CSDL XML 4.01 §14.1.2), so it is
     * an attribute on every target — Core.Description and Common.Label are such terms.
     *
     * @param list<string> $appliesToValues
     */
    private function isAttributeCandidate(array $appliesToValues, string $odataNamespace): bool
    {
        if ($odataNamespace === 'Org.OData.Capabilities.V1') {
            return false;
        }

        if ($appliesToValues === []) {
            return true;
        }
        foreach ($appliesToValues as $av) {
            if (isset(self::ATTRIBUTE_TARGET_MAP[$av])) {
                return true;
            }
        }
        return false;
    }

    /**
     * The interfaces for the APPLIES_TO constant: every target interface when AppliesTo is absent.
     *
     * @param  list<string> $appliesToValues
     * @return list<string> deduplicated interface class-strings
     */
    private function appliesToInterfaces(array $appliesToValues): array
    {
        if ($appliesToValues === []) {
            return array_values(array_unique(array_merge(...array_values(self::APPLIES_TO_INTERFACE_MAP))));
        }
        $interfaces = [];
        foreach ($appliesToValues as $av) {
            array_push($interfaces, ...(self::APPLIES_TO_INTERFACE_MAP[$av] ?? []));
        }
        return array_values(array_unique($interfaces));
    }

    /**
     * @param  list<string> $appliesToValues
     * @return list<string> deduplicated PHP constant expressions
     */
    private function resolveAttributeTargets(array $appliesToValues): array
    {
        if ($appliesToValues === []) {
            return ['Attribute::TARGET_ALL'];
        }
        $targets = [];
        foreach ($appliesToValues as $av) {
            if (!isset(self::ATTRIBUTE_TARGET_MAP[$av])) {
                continue;
            }
            array_push($targets, ...self::ATTRIBUTE_TARGET_MAP[$av]);
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


    /** Whether the qualified $type is a loaded ComplexType. */
    private function isComplexType(string $type): bool
    {
        return $this->types->isComplexType($type);
    }


    /**
     * Resolves a qualified TypeDefinition to its underlying OData type.
     * Returns the type unchanged if it is not a TypeDefinition.
     */
    private function resolveType(string $type): string
    {
        return $this->types->resolve($type);
    }

    /** Returns true when the qualified $type is a loaded EnumType. */
    private function isEnumType(string $type): bool
    {
        return $this->types->isEnumType($type);
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

        $enumInfo    = $this->types->enumType($entry->getNamespace() . '.' . $name);
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
        // A PHP enum refuses two cases with one value (fatal on first access),
        // but a vocabulary may alias a member — Common.FieldControlType has
        // Hidden = 0 beside Inapplicable = 0. The first member keeps the case;
        // an alias becomes a constant pointing at it, so the name still works.
        $lines = [];
        $seen  = [];
        foreach ($enumInfo['members'] as $m) {
            if (isset($seen[$m['value']])) {
                $lines[] = "    public const {$m['name']} = self::{$seen[$m['value']]};";
                continue;
            }
            $seen[$m['value']] = $m['name'];
            $lines[] = "    case {$m['name']} = {$m['value']};";
        }
        $cases = implode("\n", $lines);
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
            $this->emit('All term types resolved.');
            return;
        }

        $this->emit('');
        $this->emit(sprintf(
            '%d term(s) with a type no loaded vocabulary declares, written by their PHP value:',
            count($this->stubs),
        ));
        foreach ($this->stubs as $stub) {
            $this->emit("  ! {$stub}");
        }
    }
}
