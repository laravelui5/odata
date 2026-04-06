<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

/**
 * Criticality of a value or status, represented e.g. via semantic colors (https://experience.sap.com/fiori-design-web/foundation/colors/#semantic-colors)
 */
enum CriticalityType: int
{
    case VeryNegative = -1;
    case Neutral = 0;
    case Negative = 1;
    case Critical = 2;
    case Positive = 3;
    case VeryPositive = 4;
    case Information = 5;
}
