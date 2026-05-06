<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Container;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EnumMemberInterface;
use LaravelUi5\OData\Edm\HasAnnotations;

final readonly class EnumMember implements EnumMemberInterface
{
    use HasAnnotations;

    /**
     * @param list<AnnotationInterface> $annotations
     */
    public function __construct(
        private string $name,
        private int    $value,
        array          $annotations = [],
    ) {
        $this->annotations = $annotations;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): int
    {
        return $this->value;
    }
}
