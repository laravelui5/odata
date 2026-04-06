<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

enum ConformanceLevelType: int
{
    case Minimal = 0;
    case Intermediate = 1;
    case Advanced = 2;
}
