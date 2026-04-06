<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Authorization\V1;

enum KeyLocation: int
{
    case Header = 0;
    case QueryOption = 1;
    case Cookie = 2;
}
