<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

enum PhoneType: int
{
    // IsFlags = true
    case work = 1;
    case home = 2;
    case preferred = 4;
    case voice = 8;
    case cell = 16;
    case fax = 32;
    case video = 64;
}
