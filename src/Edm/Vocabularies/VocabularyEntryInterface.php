<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Vocabularies;

/**
 * A single vocabulary entry in the build-time catalog.
 *
 * Each entry carries everything the generator needs to fetch, parse,
 * and emit PHP classes for one vocabulary: the remote XML source,
 * the OData namespace and canonical alias, and the target PHP namespace
 * under which the generated classes will be placed.
 *
 * Example — the SAP UI vocabulary:
 *
 *   getNamespace()     → "com.sap.vocabularies.UI.v1"
 *   getAlias()         → "UI"
 *   getUri()           → "https://sap.github.io/odata-vocabularies/vocabularies/UI.xml"
 *   getPhpNamespace()  → "LaravelUi5\\OData\\Vocabularies\\Ui\\V1"
 *
 * Example — the OASIS Core vocabulary:
 *
 *   getNamespace()     → "Org.OData.Core.V1"
 *   getAlias()         → "Core"
 *   getUri()           → "https://oasis-tcs.github.io/odata-vocabularies/vocabularies/Org.OData.Core.V1.xml"
 *   getPhpNamespace()  → "LaravelUi5\\OData\\Vocabularies\\Core\\V1"
 */
interface VocabularyEntryInterface
{
    /**
     * The full OData namespace of this vocabulary,
     * e.g. "com.sap.vocabularies.UI.v1".
     */
    public function getNamespace(): string;

    /**
     * The canonical alias for this vocabulary as used in CSDL documents,
     * e.g. "UI". This alias is registered in the VocabularyRegistry so
     * that term names can be resolved at runtime.
     */
    public function getAlias(): string;

    /**
     * The URI of the vocabulary's CSDL XML source document. The generator
     * fetches this URI to obtain the Term and ComplexType definitions.
     */
    public function getUri(): string;

    /**
     * The PHP namespace under which the generator places the generated
     * classes for this vocabulary, e.g.
     * "LaravelUi5\\OData\\Vocabularies\\Ui\\V1".
     *
     * Generated classes for Terms and ComplexTypes are placed directly
     * in this namespace. The generator derives the class name from the
     * Term or ComplexType's simple name.
     */
    public function getPhpNamespace(): string;

    /**
     * The OData namespaces this vocabulary depends on, in no particular
     * order. The catalog uses this to validate resolution order at
     * generation time.
     *
     * @return list<string>
     */
    public function getDependencies(): array;

    /**
     * Build an edmx:Reference for this vocabulary entry.
     */
    public function toReference(): \LaravelUi5\OData\Edm\Contracts\ReferenceInterface;
}