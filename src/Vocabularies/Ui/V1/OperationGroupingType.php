<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

enum OperationGroupingType: int
{
    case Isolated = 0;
    case ChangeSet = 1;
}
