<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Service\Resolver;

use Illuminate\Database\Eloquent\Model;
use LaravelUi5\OData\Driver\Sql\EloquentEntitySetResolver;
use LaravelUi5\OData\Service\Contracts\EntitySetResolverInterface;
use LaravelUi5\OData\Service\Contracts\ResolverBindingInterface;

/**
 * Serializable binding for an Eloquent-model-backed entity set.
 *
 * Stores the model class-string and creates an EloquentEntitySetResolver
 * at runtime. This is the binding type auto-registered by discoverModel().
 */
final readonly class EloquentBinding implements ResolverBindingInterface
{
    /**
     * @param class-string<Model> $modelClass
     */
    public function __construct(public string $modelClass) {}

    public function createResolver(): EntitySetResolverInterface
    {
        return new EloquentEntitySetResolver($this->modelClass);
    }

    public function getSourceClass(): ?string
    {
        return $this->modelClass;
    }
}
