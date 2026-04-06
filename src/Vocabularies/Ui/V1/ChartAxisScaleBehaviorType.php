<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

enum ChartAxisScaleBehaviorType: int
{
    case AutoScale = 0;
    case FixedScale = 1;
}
