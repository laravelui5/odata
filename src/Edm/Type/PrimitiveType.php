<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Type;

use LaravelUi5\OData\Edm\EdmPrimitiveType;
use LaravelUi5\OData\Edm\Contracts\Type\PrimitiveTypeInterface;

/**
 * Wraps a EdmPrimitiveType case as a first-class TypeInterface participant.
 *
 * The name and qualified name are derived directly from the enum value, e.g.
 * EdmPrimitiveType::String → name "String", qualified name "Edm.String".
 */
final readonly class PrimitiveType implements PrimitiveTypeInterface
{
    public function __construct(
        private EdmPrimitiveType $primitiveType,
    ) {}

    public function getPrimitiveType(): EdmPrimitiveType
    {
        return $this->primitiveType;
    }

    public function getName(): string
    {
        return $this->primitiveType->name;
    }

    public function getQualifiedName(): string
    {
        return $this->primitiveType->value;
    }
}
