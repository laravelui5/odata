<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

enum DataModificationOperationKind: int
{
    case insert = 0;
    case update = 1;
    case upsert = 2;
    case delete = 3;
    case invoke = 4;
    case link = 5;
    case unlink = 6;
}
