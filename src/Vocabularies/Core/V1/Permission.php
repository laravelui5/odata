<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Core\V1;

enum Permission: int
{
    // IsFlags = true
    case None = 0;
    case Read = 1;
    case Write = 2;
    case ReadWrite = 3;
    case Invoke = 4;
}
