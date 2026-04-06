<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

enum ChartType: int
{
    case Column = 0;
    case ColumnStacked = 1;
    case ColumnDual = 2;
    case ColumnStackedDual = 3;
    case ColumnStacked100 = 4;
    case ColumnStackedDual100 = 5;
    case Bar = 6;
    case BarStacked = 7;
    case BarDual = 8;
    case BarStackedDual = 9;
    case BarStacked100 = 10;
    case BarStackedDual100 = 11;
    case Area = 12;
    case AreaStacked = 13;
    case AreaStacked100 = 14;
    case HorizontalArea = 15;
    case HorizontalAreaStacked = 16;
    case HorizontalAreaStacked100 = 17;
    case Line = 18;
    case LineDual = 19;
    case Combination = 20;
    case CombinationStacked = 21;
    case CombinationDual = 22;
    case CombinationStackedDual = 23;
    case HorizontalCombinationStacked = 24;
    case Pie = 25;
    case Donut = 26;
    case Scatter = 27;
    case Bubble = 28;
    case Radar = 29;
    case HeatMap = 30;
    case TreeMap = 31;
    case Waterfall = 32;
    case Bullet = 33;
    case VerticalBullet = 34;
    case HorizontalWaterfall = 35;
    case HorizontalCombinationDual = 36;
    case HorizontalCombinationStackedDual = 37;
    case Donut100 = 38;
}
