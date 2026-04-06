<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Contracts\Container;

use LaravelUi5\OData\Edm\Contracts\AnnotatableInterface;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\NamedElementInterface;

/**
 * A single member of an EnumType.
 *
 * Members carry a name and an integer value. When the parent enum
 * does not declare explicit values the spec mandates zero-based
 * sequential assignment; the resolved value is always available here.
 *
 * @see OData CSDL XML v4.01 §10.3 (Enumeration Type Member)
 */
interface EnumMemberInterface extends NamedElementInterface, AnnotatableInterface, AnnotationTargetInterface
{
    /**
     * The integer value of this member, resolved from the CSDL
     * declaration or assigned implicitly by position.
     */
    public function getValue(): int;
}
