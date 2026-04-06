<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

enum KindType: int
{
    case individual = 0;
    case group = 1;
    case org = 2;
    case location = 3;
}
