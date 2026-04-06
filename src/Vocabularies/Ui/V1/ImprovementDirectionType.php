<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Describes which direction of a value change is seen as an improvement
 */
enum ImprovementDirectionType: int
{
    case Minimize = 1;
    case Target = 2;
    case Maximize = 3;
}
