<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

enum RevisionKind: int
{
    case Added = 0;
    case Modified = 1;
    case Deprecated = 2;
}
