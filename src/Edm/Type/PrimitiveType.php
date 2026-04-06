<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Type;

use LaravelUi5\OData\Edm\Contracts\Container\PrimitiveTypeEnum;
use LaravelUi5\OData\Edm\Contracts\Container\PrimitiveTypeInterface;

/**
 * Wraps a PrimitiveTypeEnum case as a first-class TypeInterface participant.
 *
 * The name and qualified name are derived directly from the enum value, e.g.
 * PrimitiveTypeEnum::String → name "String", qualified name "Edm.String".
 */
final readonly class PrimitiveType implements PrimitiveTypeInterface
{
    public function __construct(
        private PrimitiveTypeEnum $primitiveType,
    ) {}

    public function getPrimitiveType(): PrimitiveTypeEnum
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
