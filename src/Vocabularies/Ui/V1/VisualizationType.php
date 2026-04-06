<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

enum VisualizationType: int
{
    case Number = 0;
    case BulletChart = 1;
    case Progress = 2;
    case Rating = 3;
    case Donut = 4;
    case DeltaBulletChart = 5;
}
