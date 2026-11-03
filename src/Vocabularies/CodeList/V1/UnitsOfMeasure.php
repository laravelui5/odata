<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\CodeList\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Container\EntityContainerInterface;

/**
 * An entity set containing the code list for units of measure
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class UnitsOfMeasure implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.CodeList.v1.UnitsOfMeasure';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityContainerInterface::class,
    ];

    /**
     * @param string|Path|null $url URL of a CSDL document describing an entity set for a code list
     * @param string|Path|null $collectionPath Name of the entity set for the code list
     */
    public function __construct(
        public string|Path|null $url = null,
        public string|Path|null $collectionPath = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new CodeListSource(
            url: $this->url,
            collectionPath: $this->collectionPath,
        ))->toRecord();
    }
}
