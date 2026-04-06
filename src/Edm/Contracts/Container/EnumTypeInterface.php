<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Container;

use LaravelUi5\OData\Edm\Contracts\AnnotatableInterface;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeInterface;

/**
 * An enumeration type, defining a set of named integer constants.
 *
 * EnumTypes may be used as the type of a structural property. The
 * underlying storage type is always one of the signed or unsigned
 * integer primitives. Flags enums allow bitwise combination of
 * member values.
 *
 * @see OData CSDL XML v4.01 §10 (Enumeration Type)
 */
interface EnumTypeInterface extends TypeInterface, AnnotatableInterface, AnnotationTargetInterface
{
    /**
     * The primitive integer type used to store member values.
     *
     * Must be one of: Edm.Byte, Edm.SByte, Edm.Int16, Edm.Int32,
     * Edm.Int64. Defaults to Edm.Int32 when absent in CSDL.
     *
     * @see OData CSDL XML v4.01 §10.1
     */
    public function getUnderlyingType(): PrimitiveTypeEnum;

    /**
     * Whether this enum supports bitwise combination of its members.
     *
     * @see OData CSDL XML v4.01 §10.2
     */
    public function isFlags(): bool;

    /**
     * All declared members of this enumeration, in document order.
     *
     * @return list<EnumMemberInterface>
     */
    public function getMembers(): array;

    /**
     * Returns the member with the given name, or null if absent.
     */
    public function getMember(string $name): ?EnumMemberInterface;
}
