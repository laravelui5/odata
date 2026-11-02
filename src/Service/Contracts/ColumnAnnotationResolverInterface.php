<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Contracts;

use Illuminate\Database\Eloquent\Model;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;

/**
 * The annotation forward-exit of model discovery.
 *
 * A host annotates its own model's columns on the class with
 * {@see \LaravelUi5\OData\Service\Discovery\Attributes\ODataAnnotate}. A package that knows something about columns
 * across models — which fields it translates, say, and so `Core.IsLanguageDependent` — binds its own resolver; the
 * default ({@see \LaravelUi5\OData\Service\Discovery\ColumnsUnannotated}) answers nothing.
 *
 * Asked once per declared column of every discovered model. On the same term and qualifier the model's
 * `#[ODataAnnotate]` wins: it is local to one model.
 *
 * The resolver runs when the schema is **built** — on every request for a cold service, once in `odata:cache` for a
 * cached one. A cached service keeps what it saw then.
 *
 * One binding, not a chain: rebinding replaces the previous resolver. A package that wants to keep another resolver's
 * answer decorates it.
 */
interface ColumnAnnotationResolverInterface
{
    /**
     * @param class-string<Model> $modelClass the discovered model
     * @param string              $column     the column name as in the table
     *
     * @return list<AnnotationInterface|TypedAnnotationInterface>
     */
    public function annotations(string $modelClass, string $column): array;
}
