<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

enum ImportanceType: int
{
    case High = 0;
    case Medium = 1;
    case Low = 2;
}
