<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Capabilities\V1;

enum IsolationLevel: int
{
    // IsFlags = true
    case Snapshot = 1;
}
