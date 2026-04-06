<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts;

/**
 * A schema namespace included from a referenced external document.
 *
 * An Include brings a specific namespace from the referenced document
 * into the scope of the referencing document, optionally under an
 * alias. All named elements in the included namespace become
 * addressable by their qualified name — or by alias-qualified name
 * when an alias is declared.
 *
 * @see OData CSDL XML v4.01 §4.2 (Included Schema)
 */
interface IncludedSchemaInterface
{
    /**
     * The namespace of the schema being included,
     * e.g. "Org.OData.Core.V1".
     */
    public function getNamespace(): string;

    /**
     * The alias under which this namespace is available in the
     * referencing document, e.g. "Core". Null when no alias is
     * declared.
     *
     * @see OData CSDL XML v4.01 §4.2
     */
    public function getAlias(): ?string;
}
