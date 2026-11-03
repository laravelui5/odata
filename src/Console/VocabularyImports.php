<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Console;

/**
 * The `use` lines of one generated class.
 *
 * A class of the same PHP namespace needs none. A class whose simple name is already taken, by the generated class
 * itself or by another import, is written fully qualified instead (`Common.RecursiveHierarchyType` and
 * `Aggregation.RecursiveHierarchyType` share a name).
 *
 * @internal Used by {@see VocabularyGenerator}; not part of the package API.
 */
final class VocabularyImports
{
    /** @var array<string, string> simple name → fully qualified class name */
    private array $imports = [];

    /** @var array<string, true> simple names referenced from the class's own namespace */
    private array $local = [];

    public function __construct(
        private readonly string $namespace,
        private readonly string $className,
    ) {}

    /** The name to write for `$class` in the generated code; imports it when that is safe. */
    public function reference(string $class): string
    {
        $class  = ltrim($class, '\\');
        $dot    = strrpos($class, '\\');
        $simple = $dot === false ? $class : substr($class, $dot + 1);
        $ns     = $dot === false ? '' : substr($class, 0, $dot);

        if ($ns === $this->namespace) {
            if (isset($this->imports[$simple])) {
                return '\\' . $class;
            }
            $this->local[$simple] = true;
            return $simple;
        }
        if (($this->imports[$simple] ?? $class) !== $class || $simple === $this->className || isset($this->local[$simple])) {
            return '\\' . $class;
        }
        $this->imports[$simple] = $class;

        return $simple;
    }

    /** The sorted `use` lines, empty when nothing is imported. */
    public function block(): string
    {
        $lines = array_map(static fn(string $c) => "use {$c};", array_values($this->imports));
        sort($lines);

        return implode("\n", $lines);
    }
}
