<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts;

/**
 * A reference to an external CSDL document.
 *
 * References declare that the current document depends on type
 * definitions or annotations found in another document, identified
 * by URI. Each reference may include one or more schemas from the
 * referenced document into the current scope.
 *
 * In a fully resolved model the reference serves as metadata —
 * recording which external documents were consulted when the model
 * was built. A serialiser emits these as <edmx:Reference> elements
 * in the metadata document so that clients can locate the vocabulary
 * definitions their annotations refer to.
 *
 * Common examples are the SAP and OASIS vocabulary documents:
 *
 *   uri:    "https://sap.github.io/odata-vocabularies/vocabularies/UI.xml"
 *   includes: [ { namespace: "com.sap.vocabularies.UI.v1", alias: "UI" } ]
 *
 *   uri:    "https://oasis-tcs.github.io/odata-vocabularies/vocabularies/Org.OData.Core.V1.xml"
 *   includes: [ { namespace: "Org.OData.Core.V1", alias: "Core" } ]
 *
 * @see OData CSDL XML v4.01 §4.1 (Reference)
 */
interface ReferenceInterface extends AnnotatableInterface
{
    /**
     * The URI of the referenced document.
     *
     * May be an absolute URI pointing to a published vocabulary, or a
     * relative URI resolved against the metadata document's base URI.
     *
     * @see OData CSDL XML v4.01 §4.1
     */
    public function getUri(): string;

    /**
     * The schemas included from this referenced document into the
     * current scope.
     *
     * Returns an empty array when the reference is present solely
     * to pull in external annotations via IncludeAnnotations, which
     * is outside the scope of this interface layer.
     *
     * @return list<IncludedSchemaInterface>
     * @see OData CSDL XML v4.01 §4.2
     */
    public function getIncludes(): array;

    /**
     * Returns the included schema for the given namespace, or null
     * when this reference does not include that namespace.
     * @param string $namespace
     * @return IncludedSchemaInterface|null
     */
    public function getInclude(string $namespace): ?IncludedSchemaInterface;
}
