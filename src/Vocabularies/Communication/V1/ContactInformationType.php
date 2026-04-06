<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Communication\V1;

enum ContactInformationType: int
{
    // IsFlags = true
    case work = 1;
    case home = 2;
    case preferred = 4;
}
