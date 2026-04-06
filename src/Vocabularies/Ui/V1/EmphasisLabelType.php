<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Assigns a label to the set of emphasized values and optionally also for non-emphasized values. This information can be used for semantic coloring.
 */
final readonly class EmphasisLabelType
{
    public function __construct(
        public readonly string $emphasizedValuesLabel,
        public readonly ?string $nonEmphasizedValuesLabel = null,
    ) {}
}
