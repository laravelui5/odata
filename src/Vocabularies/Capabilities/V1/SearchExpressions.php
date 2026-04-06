<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

enum SearchExpressions: int
{
    // IsFlags = true
    case none = 0;
    case AND = 1;
    case OR = 2;
    case NOT = 4;
    case phrase = 8;
    case group = 16;
}
