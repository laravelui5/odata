<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Container;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EnumMemberInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EnumTypeInterface;
use LaravelUi5\OData\Edm\EdmPrimitiveType;
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
        private EdmPrimitiveType $underlyingType = EdmPrimitiveType::Int32,
        private bool             $isFlags        = false,
        private array            $members        = [],
        array                    $annotations    = [],
    ) {
        $this->annotations = $annotations;
    }

    /**
     * Build an EnumType from a PHP int-backed enum class-string.
     *
     * The EDM short name is derived from the PHP short class name. The
     * underlying type is fixed at Edm.Int32 — matches OData v4 default
     * and covers all consumer values today. Members are emitted in
     * declaration order using PHP case names (display labels are an
     * i18n concern, not the engine's).
     *
     * String-backed and pure enums are rejected — OData v4 EnumTypes
     * are integer-keyed.
     *
     * @param class-string<\BackedEnum> $enumClass
     */
    public static function fromBackedEnum(string $namespace, string $enumClass): self
    {
        if (!enum_exists($enumClass)) {
            throw new \InvalidArgumentException(sprintf(
                'EnumType::fromBackedEnum expected a PHP enum class-string, got "%s".',
                $enumClass,
            ));
        }

        $reflection  = new \ReflectionEnum($enumClass);
        $backingType = $reflection->getBackingType();

        if ($backingType === null) {
            throw new \InvalidArgumentException(sprintf(
                'EnumType::fromBackedEnum expected a backed enum, got pure enum "%s".',
                $enumClass,
            ));
        }

        if ((string) $backingType !== 'int') {
            throw new \InvalidArgumentException(sprintf(
                'EnumType::fromBackedEnum expected an int-backed enum, got %s-backed "%s". '
                . 'OData v4 EnumTypes are integer-keyed.',
                (string) $backingType,
                $enumClass,
            ));
        }

        $members = [];
        foreach ($reflection->getCases() as $case) {
            /** @var \ReflectionEnumBackedCase $case */
            $members[] = new EnumMember($case->getName(), (int) $case->getBackingValue());
        }

        return new self(
            namespace: $namespace,
            name: $reflection->getShortName(),
            underlyingType: EdmPrimitiveType::Int32,
            members: $members,
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQualifiedName(): string
    {
        return $this->namespace . '.' . $this->name;
    }

    public function getUnderlyingType(): EdmPrimitiveType
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
