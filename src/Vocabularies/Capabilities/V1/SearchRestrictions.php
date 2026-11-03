<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntitySetInterface;
use LaravelUi5\OData\Edm\Contracts\Property\NavigationPropertyInterface;
use LaravelUi5\OData\Edm\Contracts\Property\PropertyInterface;

/**
 * Restrictions on search expressions
 * @see TypedAnnotationInterface
 */
final readonly class SearchRestrictions implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'Org.OData.Capabilities.V1.SearchRestrictions';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntitySetInterface::class,
        PropertyInterface::class,
        NavigationPropertyInterface::class,
    ];

    /**
     * @param bool|Path|null $searchable $search is supported
     * @param SearchExpressions|list<SearchExpressions>|Path|null $unsupportedExpressions Expressions not supported in $search
     */
    public function __construct(
        public bool|Path|null $searchable = null,
        public SearchExpressions|array|Path|null $unsupportedExpressions = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new SearchRestrictionsType(
            searchable: $this->searchable,
            unsupportedExpressions: $this->unsupportedExpressions,
        ))->toRecord();
    }
}
