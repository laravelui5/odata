<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Edm\Container;

use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\NavigationPropertyBindingInterface;
use LaravelUi5\OData\Edm\HasAnnotations;

final readonly class NavigationPropertyBinding implements NavigationPropertyBindingInterface
{
    use HasAnnotations;

    /**
     * @param list<AnnotationInterface> $annotations
     */
    public function __construct(
        private string $path,
        private string $target,
        array          $annotations = [],
    ) {
        $this->annotations = $annotations;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getTarget(): string
    {
        return $this->target;
    }
}
