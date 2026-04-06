<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

enum NavigationType: int
{
    case Recursive = 0;
    case Single = 1;
    case None = 2;
}
