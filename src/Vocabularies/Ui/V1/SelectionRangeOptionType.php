<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Comparison operator
 */
enum SelectionRangeOptionType: int
{
    case EQ = 0;
    case BT = 1;
    case CP = 2;
    case LE = 3;
    case GE = 4;
    case NE = 5;
    case NB = 6;
    case NP = 7;
    case GT = 8;
    case LT = 9;
}
