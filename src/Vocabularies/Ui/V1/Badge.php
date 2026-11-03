<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

use Attribute;
use LaravelUi5\OData\Edm\Annotation\Path;
use LaravelUi5\OData\Edm\Annotation\TypedAnnotationTrait;
use LaravelUi5\OData\Edm\Contracts\AnnotationTargetInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\AnnotationValueInterface;
use LaravelUi5\OData\Edm\Contracts\Annotation\TypedAnnotationInterface;
use LaravelUi5\OData\Edm\Contracts\Type\EntityTypeInterface;

/**
 * Information usually displayed in the form of a business card
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Badge implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.UI.v1.Badge';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
    ];

    public function __construct(
        public DataField|Path|null $headLine = null,
        public DataField|Path|null $title = null,
        public string|Path|null $imageUrl = null,
        public string|Path|null $typeImageUrl = null,
        public DataField|Path|null $mainInfo = null,
        public DataField|Path|null $secondaryInfo = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): ?AnnotationValueInterface
    {
        return (new BadgeType(
            headLine: $this->headLine,
            title: $this->title,
            imageUrl: $this->imageUrl,
            typeImageUrl: $this->typeImageUrl,
            mainInfo: $this->mainInfo,
            secondaryInfo: $this->secondaryInfo,
        ))->toRecord();
    }
}
