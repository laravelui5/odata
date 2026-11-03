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
 * Defines the card structure for a list visualization. Use instead of UI.LineItem when presenting entity data as cards e.g. in a grid list layout.
 * @see TypedAnnotationInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class CardItem implements TypedAnnotationInterface
{
    use TypedAnnotationTrait;

    public const string TERM = 'com.sap.vocabularies.UI.v1.CardItem';

    /** @var array<class-string<AnnotationTargetInterface>> */
    public const array APPLIES_TO = [
        EntityTypeInterface::class,
    ];

    /**
     * @param string|Path|null $header Points to a UI.HeaderInfo annotation defining the card header.
     * @param list<DataFieldAbstract>|Path|null $actions Action items for the card. Supports DataFieldForAction, DataFieldForIntentBasedNavigation, and DataFieldForActionGroup.
     * @param list<Facet>|Path|null $details Card body content as a collection of facets defining the sections of the card body.
     */
    public function __construct(
        public string|Path|null $header = null,
        public array|Path|null $actions = null,
        public array|Path|null $details = null,
        public ?string $qualifier = null,
    ) {}

    protected function buildAnnotationValue(): AnnotationValueInterface
    {
        return (new CardItemType(
            header: $this->header,
            actions: $this->actions,
            details: $this->details,
        ))->toRecord();
    }
}
