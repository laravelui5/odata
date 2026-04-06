<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

enum FilterExpressionType: int
{
    case SingleValue = 0;
    case MultiValue = 1;
    case SingleInterval = 2;
}
