<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

enum EffectType: int
{
    // IsFlags = true
    case ValidationMessage = 1;
    case ValueChange = 2;
    case FieldControlChange = 4;
}
