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

    public function withNullable(bool $nullable): self
    {
        return new self($nullable, $this->maxLength, $this->precision, $this->scale, $this->unicode, $this->srid);
    }

    public function withMaxLength(?int $maxLength): self
    {
        return new self($this->nullable, $maxLength, $this->precision, $this->scale, $this->unicode, $this->srid);
    }

    public function withPrecision(?int $precision): self
    {
        return new self($this->nullable, $this->maxLength, $precision, $this->scale, $this->unicode, $this->srid);
    }

    public function withScale(?int $scale): self
    {
        return new self($this->nullable, $this->maxLength, $this->precision, $scale, $this->unicode, $this->srid);
    }

    /**
     * True when every facet stands at the spec default (nullable, nothing else set),
     * i.e. the facets say nothing a client could not assume.
     */
    public function isDefault(): bool
    {
        return $this->nullable
            && $this->maxLength === null
            && $this->precision === null
            && $this->scale === null
            && $this->unicode === null
            && $this->srid === null;
    }
}
