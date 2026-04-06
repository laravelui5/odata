<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

enum ChartAxisAutoScaleDataScopeType: int
{
    case DataSet = 0;
    case VisibleData = 1;
}
