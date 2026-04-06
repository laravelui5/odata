<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

enum CriticalityRepresentationType: int
{
    case WithIcon = 0;
    case WithoutIcon = 1;
    case OnlyIcon = 2;
}
