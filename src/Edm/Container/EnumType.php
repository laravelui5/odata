<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Container;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EnumMemberInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EnumTypeInterface;
use LaravelUi5\OData\Edm\Contracts\Container\PrimitiveTypeEnum;
use LaravelUi5\OData\Edm\HasAnnotations;

final readonly class EnumType implements EnumTypeInterface
{
    use HasAnnotations;

    /**
     * @param list<EnumMemberInterface> $members
     * @param list<AnnotationInterface> $annotations
     */
    public function __construct(
        private string           $namespace,
        private string           $name,
        private PrimitiveTypeEnum $underlyingType = PrimitiveTypeEnum::Int32,
        private bool             $isFlags        = false,
        private array            $members        = [],
        array                    $annotations    = [],
    ) {
        $this->annotations = $annotations;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQualifiedName(): string
    {
        return $this->namespace . '.' . $this->name;
    }

    public function getUnderlyingType(): PrimitiveTypeEnum
    {
        return $this->underlyingType;
    }

    public function isFlags(): bool
    {
        return $this->isFlags;
    }

    public function getMembers(): array
    {
        return $this->members;
    }

    public function getMember(string $name): ?EnumMemberInterface
    {
        foreach ($this->members as $member) {
            if ($member->getName() === $name) {
                return $member;
            }
        }
        return null;
    }
}
