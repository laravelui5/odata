<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * The trend of a value
 */
enum TrendType: int
{
    case StrongUp = 1;
    case Up = 2;
    case Sideways = 3;
    case Down = 4;
    case StrongDown = 5;
}
