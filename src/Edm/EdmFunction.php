<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\EntitySetPathInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionInterface;
use LaravelUi5\OData\Edm\Contracts\FunctionParameterInterface;
use LaravelUi5\OData\Edm\Contracts\Type\TypeInterface;

/**
 * Concrete implementation of a function overload.
 *
 * Named EdmFunction to avoid collision with PHP's reserved word `function`.
 */
final readonly class EdmFunction implements FunctionInterface
{
    use HasAnnotations;

    /**
     * @param list<FunctionParameterInterface> $parameters
     * @param list<AnnotationInterface> $annotations
     */
    public function __construct(
        private string                $name,
        private bool                  $isBound              = false,
        private bool                  $isComposable         = false,
        private ?TypeInterface        $returnType           = null,
        private bool                  $returnsCollection    = false,
        private bool                  $isReturnTypeNullable = true,
        private array                 $parameters           = [],
        private ?EntitySetPathInterface $entitySetPath      = null,
        array                         $annotations          = [],
    ) {
        $this->annotations = $annotations;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isBound(): bool
    {
        return $this->isBound;
    }

    public function isComposable(): bool
    {
        return $this->isComposable;
    }

    public function getReturnType(): ?TypeInterface
    {
        return $this->returnType;
    }

    public function returnsCollection(): bool
    {
        return $this->returnsCollection;
    }

    public function isReturnTypeNullable(): bool
    {
        return $this->isReturnTypeNullable;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameter(string $name): ?FunctionParameterInterface
    {
        foreach ($this->parameters as $parameter) {
            if ($parameter->getName() === $name) {
                return $parameter;
            }
        }
        return null;
    }

    public function getEntitySetPath(): ?EntitySetPathInterface
    {
        return $this->entitySetPath;
    }
}
