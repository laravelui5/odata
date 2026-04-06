<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Describes how to visualise a number
 */
final readonly class NumberFormat
{
    public function __construct(
        public readonly ?float $scaleFactor = null,
        public readonly ?int $numberOfFractionalDigits = null,
    ) {}
}
