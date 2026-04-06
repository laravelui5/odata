<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Type;

use LaravelUi5\OData\Edm\Contracts\Type\TypeFacetsInterface;

final readonly class TypeFacets implements TypeFacetsInterface
{
    public function __construct(
        private bool  $nullable   = true,
        private ?int  $maxLength  = null,
        private ?int  $precision  = null,
        private ?int  $scale      = null,
        private ?bool $unicode    = null,
        private ?int  $srid       = null,
    ) {}

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    public function getScale(): ?int
    {
        return $this->scale;
    }

    public function isUnicode(): ?bool
    {
        return $this->unicode;
    }

    public function getSrid(): ?int
    {
        return $this->srid;
    }
}
