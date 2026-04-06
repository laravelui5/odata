<?php

declare(strict_types=1);

namespace LaravelUi5\OData\Vocabularies\Common\V1;

/**
 * Control state of a property
 */
enum FieldControlType: int
{
    case Mandatory = 7;
    case Optional = 3;
    case ReadOnly = 1;
    case Inapplicable = 0;
    case Hidden = 0;
}
