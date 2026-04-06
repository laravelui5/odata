<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Container;

use LaravelUi5\OData\Edm\Contracts\Type\TypeInterface;

/**
 * Represents a resolved Edm primitive type.
 *
 * This is a thin wrapper around PrimitiveTypeEnum that makes
 * primitive types first-class participants in the TypeInterface
 * hierarchy. It allows properties to declare their type uniformly
 * regardless of whether it is primitive, structured, or enumerated.
 *
 * @see OData CSDL XML v4.01 §3.3 (Primitive Types)
 */
interface PrimitiveTypeInterface extends TypeInterface
{
    /**
     * The specific primitive type this instance represents.
     */
    public function getPrimitiveType(): PrimitiveTypeEnum;
}
