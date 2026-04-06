<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Type;

/**
 * Type constraint facets that may be applied to a structural property
 * or type definition to further restrict the value space of its type.
 *
 * Not all facets are valid for every primitive type. The spec defines
 * applicability per type; enforcement is the responsibility of the
 * implementing layer, not this interface.
 *
 * All facets are optional in CSDL. Methods return null when the facet
 * is absent, meaning the type's own default applies.
 *
 * @see OData CSDL XML v4.01 §7.2 (Type Facets)
 */
interface TypeFacetsInterface
{
    /**
     * Whether a null value is permitted.
     *
     * Defaults to true when absent. Applies to all types.
     *
     * @see OData CSDL XML v4.01 §7.2.1
     */
    public function isNullable(): bool;

    /**
     * Maximum length in characters (String) or bytes (Binary).
     *
     * Null means the facet is absent (no explicit constraint).
     * The special value "max" is represented as PHP_INT_MAX by convention.
     *
     * @see OData CSDL XML v4.01 §7.2.2
     */
    public function getMaxLength(): ?int;

    /**
     * Maximum number of significant decimal digits for Decimal,
     * or the precision of temporal types in fractional seconds.
     *
     * Null means the facet is absent.
     *
     * @see OData CSDL XML v4.01 §7.2.3
     */
    public function getPrecision(): ?int;

    /**
     * Maximum number of digits to the right of the decimal point.
     *
     * Null means the facet is absent.
     * The special value "variable" is represented as -1 by convention.
     *
     * @see OData CSDL XML v4.01 §7.2.4
     */
    public function getScale(): ?int;

    /**
     * Whether String values are Unicode (true) or ASCII (false).
     *
     * Null means the facet is absent; the default is true.
     *
     * @see OData CSDL XML v4.01 §7.2.5
     */
    public function isUnicode(): ?bool;

    /**
     * Spatial Reference System Identifier for Geography/Geometry types.
     *
     * Null means the facet is absent; defaults apply per type (4326
     * for Geography, 0 for Geometry).
     *
     * @see OData CSDL XML v4.01 §7.2.6
     */
    public function getSrid(): ?int;
}
