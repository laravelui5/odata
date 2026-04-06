<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Ui\V1;

enum TextArrangementType: int
{
    case TextFirst = 0;
    case TextLast = 1;
    case TextSeparate = 2;
    case TextOnly = 3;
}
